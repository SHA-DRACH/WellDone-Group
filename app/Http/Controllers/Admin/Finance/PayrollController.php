<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\EmployeeBankDetail;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\Finance\PayrollService;
use App\Support\Excel\Workbook;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Monthly payroll: prepare a run, adjust individual payslips, have someone
 * else approve it, mark it paid, and hand the bank its transfer list.
 */
class PayrollController extends Controller
{
    public function __construct(protected PayrollService $payroll) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PayrollRun::class);

        $year = (int) $request->query('year', now()->year);
        $currencies = $this->currencies();

        return view('admin.finance.payroll.index', [
            'runs' => PayrollRun::query()
                ->whereYear('period', $year)
                ->with(['preparer:id,name', 'approver:id,name'])
                ->orderByDesc('period')
                ->get(),
            'year' => $year,
            'years' => range(now()->year + 1, now()->year - 5),
            'currencies' => $currencies,
            'eligible' => collect($currencies)->mapWithKeys(fn ($c) => [$c => $this->payroll->eligibleEmployees($c)->count()]),
            'needsRate' => collect($currencies)->mapWithKeys(fn ($c) => [$c => PayrollService::needsExchangeRate($c)]),
            'defaultRate' => setting('finance.exchange_rate'),
            'bandCurrency' => setting('finance.tax_band_currency'),
            'yearTotals' => PayrollRun::query()->finalised()->whereYear('period', $year)
                ->selectRaw('currency, SUM(total_gross) as gross, SUM(total_net) as net, SUM(total_tax) as tax, SUM(total_employer_contributions) as employer')
                ->groupBy('currency')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PayrollRun::class);

        $currency = strtoupper((string) $request->input('currency'));

        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'currency' => ['required', Rule::in($this->currencies())],
            'exchange_rate' => [PayrollService::needsExchangeRate($currency) ? 'required' : 'nullable', 'numeric', 'gt:0', 'max:1000000'],
            'pay_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'exchange_rate.required' => 'The tax bands are in '.setting('finance.tax_band_currency').', so the exchange rate to '.$currency.' is needed to work out income tax.',
        ]);

        $period = Carbon::createFromFormat('Y-m-d', $data['period'].'-01')->startOfDay();

        if (PayrollRun::query()->whereDate('period', $period->toDateString())->where('currency', $currency)->exists()) {
            return back()->withInput()->withErrors(['period' => 'There is already a '.$currency.' payroll for '.$period->format('F Y').'.']);
        }

        $run = $this->payroll->create(
            $period, $currency,
            isset($data['exchange_rate']) ? (float) $data['exchange_rate'] : null,
            $data['pay_date'] ?? null, $data['notes'] ?? null,
            $request->user(),
        );

        return redirect()->route('admin.payroll.show', $run)
            ->with('success', 'Payroll prepared for '.$run->employee_count.' '.str('employee')->plural($run->employee_count).'. Check it, then send it for approval.');
    }

    public function show(PayrollRun $run): View
    {
        $this->authorize('view', $run);

        $run->load(['preparer:id,name', 'approver:id,name']);

        return view('admin.finance.payroll.show', [
            'run' => $run,
            'payslips' => $run->payslips()->orderBy('employee_name')->get(),
        ]);
    }

    public function recalculate(PayrollRun $run): RedirectResponse
    {
        $this->authorize('update', $run);

        $this->payroll->sync($run);

        return back()->with('success', 'Recalculated from current pay records — '.$run->fresh()->employee_count.' employees.');
    }

    public function approve(Request $request, PayrollRun $run): RedirectResponse
    {
        if ($run->prepared_by === $request->user()->id && ! $request->user()->isSuperAdmin()) {
            return back()->with('error', 'You prepared this payroll, so somebody else has to approve it.');
        }

        $this->authorize('approve', $run);

        $this->payroll->approve($run, $request->user());

        return back()->with('success', 'Payroll approved. It is now locked.');
    }

    public function markPaid(PayrollRun $run): RedirectResponse
    {
        $this->authorize('markPaid', $run);

        $this->payroll->markPaid($run);

        return back()->with('success', 'Payroll marked as paid.');
    }

    public function destroy(PayrollRun $run): RedirectResponse
    {
        $this->authorize('delete', $run);

        $run->delete();

        return redirect()->route('admin.payroll.index')->with('success', 'Draft payroll deleted.');
    }

    public function updatePayslip(Request $request, PayrollRun $run, Payslip $payslip): RedirectResponse
    {
        $this->authorize('update', $run);
        abort_unless($payslip->payroll_run_id === $run->id, 404);

        $data = $request->validate([
            'overtime' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'bonus' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'one_off_label' => ['nullable', 'required_with:one_off_amount', 'string', 'max:60'],
            'one_off_amount' => ['nullable', 'required_with:one_off_label', 'numeric', 'min:0', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $oneOff = filled($data['one_off_label'] ?? null) && (float) ($data['one_off_amount'] ?? 0) > 0
            ? [['label' => $data['one_off_label'], 'amount' => round((float) $data['one_off_amount'], 2)]]
            : [];

        $this->payroll->adjust($payslip, (float) ($data['overtime'] ?? 0), (float) ($data['bonus'] ?? 0), $oneOff, $data['notes'] ?? null);

        return back()->with('success', 'Payslip for '.$payslip->employee_name.' updated.');
    }

    public function payslip(PayrollRun $run, Payslip $payslip): View
    {
        $this->authorize('view', $run);
        abort_unless($payslip->payroll_run_id === $run->id, 404);

        return view('admin.finance.payroll.payslip', ['run' => $run, 'payslip' => $payslip, 'layout' => 'admin.layouts.app']);
    }

    public function export(PayrollRun $run): BinaryFileResponse
    {
        $this->authorize('view', $run);

        $run->loadMissing('approver:id,name');
        $payslips = $run->payslips()->orderBy('employee_name')->get();
        $money = fn (string $key, string $label, bool $total = true) => ['key' => $key, 'label' => $label, 'type' => 'money', 'total' => $total];

        $register = $payslips->map(fn (Payslip $p) => [
            'number' => $p->employee_number,
            'name' => $p->employee_name,
            'department' => $p->department,
            'position' => $p->position,
            'basic' => $p->basic_salary,
            'allowances' => $p->allowances_total,
            'overtime' => $p->overtime,
            'bonus' => $p->bonus,
            'gross' => $p->gross_pay,
            'tax' => $p->income_tax,
            'ss' => $p->social_security,
            'other' => $p->other_deductions_total,
            'deductions' => $p->total_deductions,
            'net' => $p->net_pay,
            'employer' => $p->employer_social_security,
        ]);

        $bank = $payslips->map(fn (Payslip $p) => [
            'name' => $p->employee_name,
            'number' => $p->employee_number,
            'bank' => $p->bank_name,
            'account' => $p->account_number,
            'net' => $p->net_pay,
            'reference' => 'Salary '.$run->period->format('M Y'),
        ]);

        $title = 'Payroll '.$run->period->format('F Y').' ('.$run->currency.')';
        $status = 'Status: '.$run->status_label.($run->approver ? ' — approved by '.$run->approver->name : '');

        return Workbook::make('payroll-'.$run->period->format('Y-m').'-'.$run->currency, $title)
            ->sheet('Payroll register', [
                ['key' => 'number', 'label' => 'Staff no.'],
                ['key' => 'name', 'label' => 'Employee', 'width' => 26],
                ['key' => 'department', 'label' => 'Department', 'width' => 20],
                ['key' => 'position', 'label' => 'Position', 'width' => 22],
                $money('basic', 'Basic salary'),
                $money('allowances', 'Allowances'),
                $money('overtime', 'Overtime'),
                $money('bonus', 'Bonus'),
                $money('gross', 'Gross pay'),
                $money('tax', 'Income tax'),
                $money('ss', 'Social security'),
                $money('other', 'Other deductions'),
                $money('deductions', 'Total deductions'),
                $money('net', 'Net pay'),
                $money('employer', 'Employer social security'),
            ], $register, $title, $status)
            ->sheet('Bank transfers', [
                ['key' => 'name', 'label' => 'Account holder', 'width' => 26],
                ['key' => 'number', 'label' => 'Staff no.'],
                ['key' => 'bank', 'label' => 'Bank', 'width' => 22],
                ['key' => 'account', 'label' => 'Account no.', 'width' => 22],
                $money('net', 'Amount ('.$run->currency.')'),
                ['key' => 'reference', 'label' => 'Payment reference', 'width' => 22],
            ], $bank, 'Bank transfer list — '.$title, $status)
            ->download();
    }

    /** Currencies anyone is paid in, plus the base currency. */
    protected function currencies(): array
    {
        return EmployeeBankDetail::query()
            ->whereNotNull('currency')
            ->distinct()
            ->pluck('currency')
            ->map(fn ($c) => strtoupper($c))
            ->push(strtoupper((string) setting('finance.currency', 'USD')))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
