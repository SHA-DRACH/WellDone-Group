<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
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
 * Employee files: contracts, ID copies, permits, certificates.
 *
 * The register can be exported, but the files themselves never leave except
 * one at a time through download(), each behind a policy check — an Excel
 * sheet listing passport numbers is a document in its own right, which is
 * why the document number column is left out of it.
 */
class EmployeeDocumentController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $query = $this->filtered($request);
        $all = fn () => EmployeeDocument::query()->visibleTo($request->user());

        return view('admin.hr.documents.index', [
            'records' => (clone $query)
                ->with(['employee:id,first_name,middle_name,last_name,employee_number,photo', 'uploader:id,name'])
                ->orderByRaw('expiry_date IS NULL, expiry_date ASC')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'summary' => [
                'total' => $all()->count(),
                'expired' => $all()->expired()->count(),
                'expiring' => $all()->expiringWithin(60)->count(),
                'missing_contracts' => Employee::query()
                    ->visibleToStaff($request->user())
                    ->employed()
                    ->whereDoesntHave('documents', fn (Builder $q) => $q->where('type', 'contract'))
                    ->count(),
            ],
            'filters' => $request->only(['q', 'type', 'expiry']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', EmployeeDocument::class);

        return view('admin.hr.documents.form', [
            'record' => new EmployeeDocument(['employee_id' => $request->integer('employee') ?: null, 'type' => $request->query('type', 'contract')]),
            'employees' => $this->employeeOptions($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EmployeeDocument::class);

        $data = $this->validated($request, null);
        $data['uploaded_by'] = $request->user()->id;
        $data += $this->storeFile($request);

        $document = EmployeeDocument::query()->create($data);

        return $this->redirectAfterSave($request, $document, 'Document added to the employee file.');
    }

    public function edit(Request $request, EmployeeDocument $document): View
    {
        $this->authorize('update', $document);

        return view('admin.hr.documents.form', [
            'record' => $document,
            'employees' => $this->employeeOptions($request),
        ]);
    }

    public function update(Request $request, EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $data = $this->validated($request, $document);

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete((string) $document->file_path);
            $data += $this->storeFile($request);
        }

        $document->update($data);

        return $this->redirectAfterSave($request, $document, 'Document updated.');
    }

    public function destroy(EmployeeDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return back()->with('success', 'Document removed from the employee file.');
    }

    public function download(EmployeeDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless($document->file_path && Storage::disk('local')->exists($document->file_path), 404);

        activity('employee-document')
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->event('downloaded')
            ->log('Downloaded '.$document->type_label);

        return Storage::disk('local')->download(
            $document->file_path,
            str($document->employee?->full_name.' '.$document->type_label)->slug().'.'.pathinfo($document->file_path, PATHINFO_EXTENSION),
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', EmployeeDocument::class);

        $rows = $this->filtered($request)
            ->with(['employee:id,first_name,middle_name,last_name,employee_number', 'uploader:id,name'])
            ->orderBy('employee_id')
            ->lazy(500)
            ->map(fn (EmployeeDocument $d) => [
                'employee_number' => $d->employee?->employee_number,
                'employee' => $d->employee?->full_name,
                'type' => $d->type_label,
                'title' => $d->title,
                'issued' => $d->issue_date,
                'expires' => $d->expiry_date,
                'state' => $d->expiry_label,
                'file' => $d->file_path ? 'Yes' : 'No',
                'uploaded_by' => $d->uploader?->name,
                'uploaded_at' => $d->created_at,
            ]);

        return Workbook::make('employee-documents', 'Employee Documents Register')
            ->sheet('Documents', [
                ['key' => 'employee_number', 'label' => 'Staff no.'],
                ['key' => 'employee', 'label' => 'Employee', 'width' => 26],
                ['key' => 'type', 'label' => 'Document type', 'width' => 24],
                ['key' => 'title', 'label' => 'Title', 'width' => 30],
                ['key' => 'issued', 'label' => 'Issued', 'type' => 'date'],
                ['key' => 'expires', 'label' => 'Expires', 'type' => 'date'],
                ['key' => 'state', 'label' => 'Validity', 'width' => 22],
                ['key' => 'file', 'label' => 'File on record'],
                ['key' => 'uploaded_by', 'label' => 'Uploaded by'],
                ['key' => 'uploaded_at', 'label' => 'Uploaded at', 'type' => 'datetime'],
            ], $rows, subtitle: 'Document numbers are omitted; they are on the individual records')
            ->download();
    }

    // ------------------------------------------------------------------

    protected function filtered(Request $request): Builder
    {
        $term = trim((string) $request->query('q'));

        return EmployeeDocument::query()
            ->visibleTo($request->user())
            ->when($term, fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('title', 'like', "%{$term}%")
                ->orWhereHas('employee', fn (Builder $e) => $e->search($term))))
            ->when($request->query('type'), fn (Builder $q, $t) => $q->where('type', $t))
            ->when($request->query('expiry') === 'expired', fn (Builder $q) => $q->expired())
            ->when($request->query('expiry') === 'expiring', fn (Builder $q) => $q->expiringWithin(60));
    }

    protected function validated(Request $request, ?EmployeeDocument $document): array
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->whereNull('deleted_at')],
            'type' => ['required', Rule::in(array_keys(EmployeeDocument::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:120'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'file' => [$document?->file_path ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:15360'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ], [
            'file.required' => 'Attach the scanned document or file.',
        ]);

        $this->authorize('view', Employee::query()->findOrFail($data['employee_id']));

        unset($data['file']);

        return $data;
    }

    /** @return array<string, mixed> */
    protected function storeFile(Request $request): array
    {
        if (! $request->hasFile('file')) {
            return [];
        }

        $file = $request->file('file');

        return [
            'file_path' => $this->uploader->privateFile($file, 'hr/documents'),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'file_size' => $file->getSize(),
        ];
    }

    protected function employeeOptions(Request $request)
    {
        return Employee::query()
            ->visibleToStaff($request->user())
            ->ordered()
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'employee_number'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name.' ('.$e->employee_number.')']);
    }

    protected function redirectAfterSave(Request $request, EmployeeDocument $document, string $message): RedirectResponse
    {
        return $request->input('return') === 'employee'
            ? redirect()->to(route('admin.employees.show', $document->employee_id).'#tab-documents')->with('success', $message)
            : redirect()->route('admin.employee-documents.index')->with('success', $message);
    }
}
