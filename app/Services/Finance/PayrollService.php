<?php

namespace App\Services\Finance;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Produces and maintains payroll runs.
 *
 * Who is paid: everyone currently employed whose pay record has a basic
 * salary in the run's currency. Someone paid in another currency goes on
 * that currency's run — mixing currencies in one total would make it
 * meaningless.
 */
class PayrollService
{
    public function calculator(?float $exchangeRate): PayrollCalculator
    {
        $bands = setting('finance.tax_bands', []);
        $bands = is_array($bands) ? array_values(array_filter($bands, fn ($b) => isset($b['from'], $b['rate']))) : [];

        return new PayrollCalculator(
            $bands,
            $exchangeRate && $exchangeRate > 0 ? $exchangeRate : 1.0,
            (float) setting('finance.social_security_employee_rate', 0),
            (float) setting('finance.social_security_employer_rate', 0),
        );
    }

    /** Does working out tax need an exchange rate for this currency? */
    public static function needsExchangeRate(string $currency): bool
    {
        $bandCurrency = strtoupper((string) setting('finance.tax_band_currency', $currency));

        return $bandCurrency !== strtoupper($currency) && ! empty(setting('finance.tax_bands', []));
    }

    public function eligibleEmployees(string $currency): Builder
    {
        return Employee::query()
            ->employed()
            ->whereHas('bankDetails', fn (Builder $q) => $q
                ->where('currency', $currency)
                ->where('basic_salary', '>', 0));
    }

    public function create(Carbon $period, string $currency, ?float $exchangeRate, ?string $payDate, ?string $notes, User $by): PayrollRun
    {
        return DB::transaction(function () use ($period, $currency, $exchangeRate, $payDate, $notes, $by) {
            $run = PayrollRun::query()->create([
                'reference' => 'PAY-'.$period->format('Y-m').'-'.$currency,
                'period' => $period->copy()->startOfMonth(),
                'pay_date' => $payDate ?: $period->copy()->endOfMonth(),
                'currency' => $currency,
                'exchange_rate' => $exchangeRate,
                'status' => 'draft',
                'notes' => $notes,
                'prepared_by' => $by->id,
            ]);

            $this->sync($run);

            return $run;
        });
    }

    /**
     * Bring a draft up to date: add anyone newly eligible, drop anyone no
     * longer eligible, and recalculate everyone from their current pay
     * record. One-off overtime, bonuses and deductions keyed in on a payslip
     * are kept.
     */
    public function sync(PayrollRun $run): void
    {
        abort_unless($run->isDraft(), 422, 'Only a draft payroll can be recalculated.');

        DB::transaction(function () use ($run) {
            $calculator = $this->calculator($run->exchange_rate ? (float) $run->exchange_rate : null);

            $employees = $this->eligibleEmployees($run->currency)
                ->with(['bankDetails', 'department:id,name', 'position:id,title'])
                ->get();

            $run->payslips()->whereNotIn('employee_id', $employees->pluck('id'))->delete();

            $existing = $run->payslips()->get()->keyBy('employee_id');

            foreach ($employees as $employee) {
                $previous = $existing->get($employee->id);
                $bank = $employee->bankDetails;

                $figures = $calculator->calculate(
                    PayrollCalculator::monthly((float) $bank->basic_salary, $bank->pay_frequency),
                    self::lines($bank->allowances),
                    (float) ($previous->overtime ?? 0),
                    (float) ($previous->bonus ?? 0),
                    array_merge(self::lines($bank->deductions), self::oneOffDeductions($previous)),
                );

                $run->payslips()->updateOrCreate(
                    ['employee_id' => $employee->id],
                    $figures + [
                        'employee_number' => $employee->employee_number,
                        'employee_name' => $employee->full_name,
                        'department' => $employee->department?->name,
                        'position' => $employee->position?->title,
                        'bank_name' => $bank->bank_name,
                        'account_number' => $bank->account_number,
                        'notes' => $previous?->notes,
                    ],
                );
            }

            $run->recalculateTotals();
        });
    }

    /**
     * Change the one-off items on a single payslip — overtime, a bonus, a
     * one-off deduction — and recalculate it.
     *
     * @param  array<int, array{label: string, amount: float}>  $oneOff
     */
    public function adjust(Payslip $payslip, float $overtime, float $bonus, array $oneOff, ?string $notes): void
    {
        $run = $payslip->run;
        abort_unless($run->isDraft(), 422, 'This payroll has been approved and can no longer be changed.');

        $employee = $payslip->employee()->with('bankDetails')->first();
        $bank = $employee?->bankDetails;

        // Recurring items come from the pay record; one-offs are marked so
        // the next recalculation knows which ones to keep.
        $recurring = self::lines($bank?->deductions);
        $oneOff = array_map(fn ($line) => $line + ['one_off' => true], $oneOff);

        $figures = $this->calculator($run->exchange_rate ? (float) $run->exchange_rate : null)->calculate(
            $bank ? PayrollCalculator::monthly((float) $bank->basic_salary, $bank->pay_frequency) : (float) $payslip->basic_salary,
            $bank ? self::lines($bank->allowances) : ($payslip->allowances ?? []),
            $overtime,
            $bonus,
            array_merge($recurring, $oneOff),
        );

        $payslip->update($figures + ['notes' => $notes]);
        $run->recalculateTotals();
    }

    public function approve(PayrollRun $run, User $by): void
    {
        $run->update(['status' => 'approved', 'approved_by' => $by->id, 'approved_at' => now()]);
    }

    public function markPaid(PayrollRun $run): void
    {
        $run->update(['status' => 'paid', 'paid_at' => now()]);
    }

    /** @return array<int, array{label: string, amount: float}> */
    public static function lines(mixed $lines): array
    {
        if (! is_array($lines)) {
            return [];
        }

        $out = [];

        foreach ($lines as $key => $line) {
            // Accept both [{label, amount}] and {"Housing": 150}.
            [$label, $amount] = is_array($line)
                ? [$line['label'] ?? 'Item', $line['amount'] ?? 0]
                : [(string) $key, $line];

            if ((float) $amount > 0) {
                $out[] = ['label' => (string) $label, 'amount' => round((float) $amount, 2)];
            }
        }

        return $out;
    }

    /** One-off deductions previously keyed in on this payslip. */
    private static function oneOffDeductions(?Payslip $payslip): array
    {
        return array_values(array_filter($payslip?->other_deductions ?? [], fn ($line) => ! empty($line['one_off'])));
    }
}
