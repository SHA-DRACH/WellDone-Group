<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Division;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\MediaUploader;
use App\Support\DivisionScope;
use App\Support\Excel\Workbook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Expense claims and spending: submitted by anyone with the right, approved
 * by finance or the CEO, then marked paid.
 *
 * Division staff see and file their own division's expenses only — the
 * division on the form is forced to theirs whatever is posted.
 */
class ExpenseController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $query = $this->filtered($request);

        return view('admin.finance.expenses.index', [
            'records' => (clone $query)
                ->with(['category:id,name', 'division:id,name', 'submitter:id,name'])
                ->orderByDesc('expense_date')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString(),
            'summary' => [
                'count' => (clone $query)->count(),
                'total' => (float) (clone $query)->sum('amount'),
                'pending' => (float) (clone $query)->where('status', 'pending')->sum('amount'),
                'pending_count' => (clone $query)->where('status', 'pending')->count(),
                'counted' => (float) (clone $query)->counted()->sum('amount'),
            ],
            'categories' => ExpenseCategory::query()->ordered()->pluck('name', 'id'),
            'divisions' => $this->divisionOptions($request),
            'filters' => $request->only(['q', 'status', 'category', 'division', 'from', 'to']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Expense::class);

        return view('admin.finance.expenses.form', $this->formData($request, new Expense([
            'expense_date' => today(),
            'currency' => setting('finance.currency', 'USD'),
            'division_id' => DivisionScope::idFor($request->user()) ?: $request->integer('division') ?: null,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $data = $this->validated($request);
        $data['submitted_by'] = $request->user()->id;
        $data['status'] = 'pending';

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $this->uploader->privateFile($request->file('receipt'), 'finance/receipts');
        }

        $expense = Expense::query()->create($data);

        return redirect()->route('admin.expenses.index')
            ->with('success', $expense->reference.' submitted for approval.');
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view', $expense);

        $expense->load(['category', 'division', 'department', 'submitter:id,name', 'approver:id,name']);

        return view('admin.finance.expenses.show', ['expense' => $expense]);
    }

    public function edit(Request $request, Expense $expense): View
    {
        $this->authorize('update', $expense);

        return view('admin.finance.expenses.form', $this->formData($request, $expense));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $data = $this->validated($request);

        if ($request->hasFile('receipt')) {
            Storage::disk('local')->delete((string) $expense->receipt_path);
            $data['receipt_path'] = $this->uploader->privateFile($request->file('receipt'), 'finance/receipts');
        }

        $expense->update($data);

        return redirect()->route('admin.expenses.show', $expense)->with('success', $expense->reference.' updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('success', $expense->reference.' deleted.');
    }

    public function approve(Request $request, Expense $expense): RedirectResponse
    {
        if ($expense->submitted_by === $request->user()->id && ! $request->user()->isSuperAdmin()) {
            return back()->with('error', 'You submitted this expense, so somebody else has to approve it.');
        }

        $this->authorize('approve', $expense);

        $expense->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        return back()->with('success', $expense->reference.' approved.');
    }

    public function reject(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('approve', $expense);

        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);

        $expense->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        return back()->with('success', $expense->reference.' rejected.');
    }

    public function markPaid(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('markPaid', $expense);

        $data = $request->validate([
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
        ]);

        $expense->update($data + ['status' => 'paid']);

        return back()->with('success', $expense->reference.' marked as paid.');
    }

    public function receipt(Expense $expense): StreamedResponse
    {
        $this->authorize('view', $expense);

        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        return Storage::disk('local')->download(
            $expense->receipt_path,
            $expense->reference.'-receipt.'.pathinfo($expense->receipt_path, PATHINFO_EXTENSION),
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Expense::class);

        $rows = $this->filtered($request)
            ->with(['category:id,name', 'division:id,name', 'department:id,name', 'submitter:id,name', 'approver:id,name'])
            ->orderBy('expense_date')
            ->lazy(500)
            ->map(fn (Expense $e) => [
                'reference' => $e->reference,
                'date' => $e->expense_date,
                'category' => $e->category?->name,
                'division' => $e->division?->name ?? 'Head office',
                'department' => $e->department?->name,
                'payee' => $e->payee,
                'description' => $e->description,
                'amount' => $e->amount,
                'currency' => $e->currency,
                'status' => $e->status_label,
                'method' => $e->payment_method_label,
                'paid_on' => $e->paid_on,
                'submitted_by' => $e->submitter?->name,
                'approved_by' => $e->approver?->name,
                'receipt' => $e->receipt_path ? 'Yes' : 'No',
            ]);

        $period = collect([$request->query('from'), $request->query('to')])->filter()->implode(' to ');

        return Workbook::make('expenses', 'Expenses Register')
            ->sheet('Expenses', [
                ['key' => 'reference', 'label' => 'Reference', 'width' => 17],
                ['key' => 'date', 'label' => 'Date', 'type' => 'date'],
                ['key' => 'category', 'label' => 'Category', 'width' => 22],
                ['key' => 'division', 'label' => 'Division', 'width' => 22],
                ['key' => 'department', 'label' => 'Department', 'width' => 20],
                ['key' => 'payee', 'label' => 'Payee', 'width' => 24],
                ['key' => 'description', 'label' => 'Description', 'width' => 40, 'wrap' => true],
                ['key' => 'amount', 'label' => 'Amount', 'type' => 'money', 'total' => true],
                ['key' => 'currency', 'label' => 'Currency'],
                ['key' => 'status', 'label' => 'Status', 'width' => 18],
                ['key' => 'method', 'label' => 'Paid by'],
                ['key' => 'paid_on', 'label' => 'Paid on', 'type' => 'date'],
                ['key' => 'submitted_by', 'label' => 'Submitted by'],
                ['key' => 'approved_by', 'label' => 'Approved / rejected by', 'width' => 22],
                ['key' => 'receipt', 'label' => 'Receipt'],
            ], $rows, subtitle: $period ? 'Period: '.$period : 'All dates')
            ->download();
    }

    // ------------------------------------------------------------------

    protected function filtered(Request $request): Builder
    {
        $term = trim((string) $request->query('q'));

        return Expense::query()
            ->withinDivisionOf($request->user())
            ->when($term, fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('reference', 'like', "%{$term}%")
                ->orWhere('payee', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")))
            ->when($request->query('status'), fn (Builder $q, $s) => $q->where('status', $s))
            ->when($request->query('category'), fn (Builder $q, $c) => $q->where('expense_category_id', $c))
            ->when($request->query('division') === 'none', fn (Builder $q) => $q->whereNull('division_id'))
            ->when(is_numeric($request->query('division')), fn (Builder $q) => $q->where('division_id', $request->query('division')))
            ->between($request->query('from'), $request->query('to'));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'expense_category_id' => ['required', Rule::exists('expense_categories', 'id')],
            'division_id' => ['nullable', Rule::exists('divisions', 'id')],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'payee' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000000'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_method' => ['nullable', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        unset($data['receipt']);
        $data['currency'] = strtoupper($data['currency']);

        // A division user files under their own division, whatever was posted.
        if (($scope = DivisionScope::idFor($request->user())) !== null) {
            $data['division_id'] = $scope;
        }

        return $data;
    }

    protected function formData(Request $request, Expense $expense): array
    {
        return [
            'record' => $expense,
            'categories' => ExpenseCategory::query()->active()->ordered()->pluck('name', 'id'),
            'divisions' => $this->divisionOptions($request),
            'departments' => Department::query()->ordered()->pluck('name', 'id'),
            'scoped' => DivisionScope::restricts($request->user()),
        ];
    }

    protected function divisionOptions(Request $request)
    {
        return Division::query()
            ->when(DivisionScope::idFor($request->user()), fn (Builder $q, $id) => $q->whereKey($id))
            ->ordered()
            ->pluck('name', 'id');
    }
}
