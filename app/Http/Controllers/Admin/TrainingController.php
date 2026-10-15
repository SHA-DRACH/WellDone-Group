<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeTraining;
use App\Services\MediaUploader;
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
 * Training and certification across the workforce.
 *
 * Every query starts from the employees the user may see, so a department
 * manager's training list is their department's and nothing more.
 */
class TrainingController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeTraining::class);

        $query = $this->filtered($request);

        return view('admin.hr.training.index', [
            'records' => (clone $query)
                ->with(['employee:id,first_name,middle_name,last_name,employee_number,photo,department_id', 'employee.department:id,name'])
                ->orderByRaw('start_date IS NULL, start_date DESC')
                ->paginate(20)
                ->withQueryString(),
            'summary' => [
                'total' => (clone $query)->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'hours' => (float) (clone $query)->where('status', 'completed')->sum('hours'),
                'cost' => (float) (clone $query)->sum('cost'),
                'expiring' => EmployeeTraining::query()->visibleTo($request->user())->expiringWithin(60)->count(),
                'expired' => EmployeeTraining::query()->visibleTo($request->user())->expired()->count(),
            ],
            'departments' => Department::query()->withinDivisionOf($request->user())->ordered()->pluck('name', 'id'),
            'filters' => $request->only(['q', 'status', 'category', 'department', 'expiry', 'from', 'to']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', EmployeeTraining::class);

        return view('admin.hr.training.form', [
            'record' => new EmployeeTraining([
                'employee_id' => $request->integer('employee') ?: null,
                'status' => 'planned',
                'category' => 'technical',
            ]),
            'employees' => $this->employeeOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeTraining::class);

        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('certificate')) {
            $data['certificate_path'] = $this->uploader->privateFile($request->file('certificate'), 'hr/certificates');
        }

        $training = EmployeeTraining::query()->create($data);

        return $this->redirectAfterSave($request, $training, 'Training record added.');
    }

    public function edit(Request $request, EmployeeTraining $training): View
    {
        $this->authorize('update', $training);

        return view('admin.hr.training.form', [
            'record' => $training,
            'employees' => $this->employeeOptions($request),
        ]);
    }

    public function update(Request $request, EmployeeTraining $training): RedirectResponse
    {
        $this->authorize('update', $training);

        $data = $this->validated($request);

        if ($request->boolean('remove_certificate') && $training->certificate_path) {
            Storage::disk('local')->delete($training->certificate_path);
            $data['certificate_path'] = null;
        }

        if ($request->hasFile('certificate')) {
            Storage::disk('local')->delete((string) $training->certificate_path);
            $data['certificate_path'] = $this->uploader->privateFile($request->file('certificate'), 'hr/certificates');
        }

        $training->update($data);

        return $this->redirectAfterSave($request, $training, 'Training record updated.');
    }

    public function destroy(EmployeeTraining $training): RedirectResponse
    {
        $this->authorize('delete', $training);

        $training->delete();

        return back()->with('success', 'Training record deleted.');
    }

    public function certificate(EmployeeTraining $training): StreamedResponse
    {
        $this->authorize('view', $training);

        abort_unless($training->certificate_path && Storage::disk('local')->exists($training->certificate_path), 404);

        return Storage::disk('local')->download(
            $training->certificate_path,
            str($training->employee?->full_name.' '.$training->title)->slug().'.'.pathinfo($training->certificate_path, PATHINFO_EXTENSION),
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', EmployeeTraining::class);

        $rows = $this->filtered($request)
            ->with(['employee:id,first_name,middle_name,last_name,employee_number,department_id', 'employee.department:id,name'])
            ->orderBy('start_date')
            ->lazy(500)
            ->map(fn (EmployeeTraining $t) => [
                'employee_number' => $t->employee?->employee_number,
                'employee' => $t->employee?->full_name,
                'department' => $t->employee?->department?->name,
                'title' => $t->title,
                'category' => $t->category_label,
                'provider' => $t->provider,
                'start' => $t->start_date,
                'end' => $t->end_date,
                'hours' => $t->hours,
                'cost' => $t->cost,
                'status' => $t->status_label,
                'result' => $t->result,
                'certificate' => $t->certificate_number,
                'expiry' => $t->certificate_expiry,
                'expiry_state' => $t->expiry_label,
            ]);

        return Workbook::make('training-register', 'Training & Certification Register')
            ->sheet('Training', [
                ['key' => 'employee_number', 'label' => 'Staff no.'],
                ['key' => 'employee', 'label' => 'Employee', 'width' => 26],
                ['key' => 'department', 'label' => 'Department', 'width' => 22],
                ['key' => 'title', 'label' => 'Course / programme', 'width' => 34],
                ['key' => 'category', 'label' => 'Category', 'width' => 20],
                ['key' => 'provider', 'label' => 'Provider', 'width' => 22],
                ['key' => 'start', 'label' => 'Start', 'type' => 'date'],
                ['key' => 'end', 'label' => 'End', 'type' => 'date'],
                ['key' => 'hours', 'label' => 'Hours', 'type' => 'number', 'total' => true],
                ['key' => 'cost', 'label' => 'Cost', 'type' => 'money', 'total' => true],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'result', 'label' => 'Result'],
                ['key' => 'certificate', 'label' => 'Certificate no.'],
                ['key' => 'expiry', 'label' => 'Certificate expiry', 'type' => 'date', 'width' => 18],
                ['key' => 'expiry_state', 'label' => 'Validity', 'width' => 22],
            ], $rows)
            ->download();
    }

    // ------------------------------------------------------------------

    protected function filtered(Request $request): Builder
    {
        $term = trim((string) $request->query('q'));

        return EmployeeTraining::query()
            ->visibleTo($request->user())
            ->when($term, fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('title', 'like', "%{$term}%")
                ->orWhere('provider', 'like', "%{$term}%")
                ->orWhereHas('employee', fn (Builder $e) => $e->search($term))))
            ->when($request->query('status'), fn (Builder $q, $s) => $q->where('status', $s))
            ->when($request->query('category'), fn (Builder $q, $c) => $q->where('category', $c))
            ->when($request->query('department'), fn (Builder $q, $d) => $q->whereHas('employee', fn (Builder $e) => $e->where('department_id', $d)))
            ->when($request->query('from'), fn (Builder $q, $d) => $q->whereDate('start_date', '>=', $d))
            ->when($request->query('to'), fn (Builder $q, $d) => $q->whereDate('start_date', '<=', $d))
            ->when($request->query('expiry') === 'expired', fn (Builder $q) => $q->expired())
            ->when($request->query('expiry') === 'expiring', fn (Builder $q) => $q->expiringWithin(60));
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(EmployeeTraining::CATEGORIES))],
            'provider' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'status' => ['required', Rule::in(array_keys(EmployeeTraining::STATUSES))],
            'result' => ['nullable', 'string', 'max:255'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'certificate_expiry' => ['nullable', 'date'],
            'certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        // The employee must be one this user may see — the select only
        // offers those, but a hand-made POST is not limited to the select.
        $this->authorize('view', Employee::query()->findOrFail($data['employee_id']));

        unset($data['certificate']);

        return $data;
    }

    protected function employeeOptions(Request $request)
    {
        return Employee::query()
            ->visibleToStaff($request->user())
            ->ordered()
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'employee_number'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name.' ('.$e->employee_number.')']);
    }

    protected function redirectAfterSave(Request $request, EmployeeTraining $training, string $message): RedirectResponse
    {
        return $request->input('return') === 'employee'
            ? redirect()->to(route('admin.employees.show', $training->employee_id).'#tab-training')->with('success', $message)
            : redirect()->route('admin.training.index')->with('success', $message);
    }
}
