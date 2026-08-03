<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends BaseCrudController
{
    protected string $model = Department::class;

    protected string $viewPath = 'admin.departments';

    protected string $routeName = 'departments';

    protected string $label = 'Department';

    protected string $labelPlural = 'Departments';

    protected ?string $subtitle = 'Internal units staff report into. A department may map to a public business division where the two correspond.';

    protected array $searchable = ['name', 'code', 'description'];

    protected array $indexWith = ['division:id,name', 'head:id,first_name,last_name'];

    protected array $indexWithCount = ['employees', 'positions'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('departments', 'slug')->ignore($model?->id)],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($model?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'head_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'name' => ['label' => 'Department', 'type' => 'title', 'sub' => 'description'],
            'code' => ['label' => 'Code'],
            'division.name' => ['label' => 'Division'],
            'employees_count' => ['label' => 'Staff', 'type' => 'number', 'align' => 'end'],
            'positions_count' => ['label' => 'Positions', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Active', 'false' => 'Closed'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'name' => ['label' => 'Department name', 'required' => true, 'col' => 8, 'id' => 'dept-name'],
            'code' => ['label' => 'Code', 'col' => 4, 'placeholder' => 'HR',
                'help' => 'Short identifier used in reports.'],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#dept-name'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 6, 'min' => 0],
            'description' => ['type' => 'textarea', 'label' => 'What this department does', 'rows' => 3],
            'email' => ['label' => 'Department email', 'input' => 'email', 'col' => 6],
            'phone' => ['label' => 'Department phone', 'col' => 6],

            'division_id' => ['type' => 'select', 'label' => 'Business division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Central function (no division)',
                'help' => 'Only set this where the department mirrors a public division.'],
            'head_employee_id' => ['type' => 'select', 'label' => 'Department head', 'panel' => 'side',
                'options' => Employee::query()->employed()->ordered()->get()
                    ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name])->all(),
                'placeholder' => 'Not appointed'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Active', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    /**
     * A department holding staff is not deleted — that would orphan employee
     * records and quietly break every report grouped by department.
     */
    public function destroy(string|int $id): RedirectResponse
    {
        $record = $this->findForEdit($id);

        $this->authorize('delete', $record);

        if ($record->employees()->exists()) {
            return back()->with('error',
                'This department still has employees. Move them to another department first.');
        }

        $record->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department archived.');
    }
}
