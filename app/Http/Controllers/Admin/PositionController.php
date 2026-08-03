<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionController extends BaseCrudController
{
    protected string $model = Position::class;

    protected string $viewPath = 'admin.positions';

    protected string $routeName = 'positions';

    protected string $label = 'Position';

    protected string $labelPlural = 'Positions';

    protected ?string $subtitle = 'Job titles staff are appointed to. Grade is a seniority band — 1 is most senior.';

    protected array $searchable = ['title', 'description'];

    protected array $indexWith = ['department:id,name'];

    protected array $indexWithCount = ['employees'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('positions', 'slug')->ignore($model?->id)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'grade' => ['required', 'integer', 'min:1', 'max:10'],
            'min_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'max_salary' => ['nullable', 'numeric', 'min:0', 'max:99999999999', 'gte:min_salary'],
            'headcount_budget' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query->when($request->query('department'), fn (Builder $q, $id) => $q->where('department_id', $id));
    }

    protected function filters(): array
    {
        return [[
            'name' => 'department',
            'placeholder' => 'All departments',
            'options' => Department::query()->ordered()->pluck('name', 'id')->all(),
        ]];
    }

    protected function columns(): array
    {
        return [
            'title' => ['label' => 'Position', 'type' => 'title', 'sub' => 'description'],
            'department.name' => ['label' => 'Department'],
            'grade_label' => ['label' => 'Band', 'type' => 'badge', 'variant' => 'secondary'],
            'employees_count' => ['label' => 'Filled', 'type' => 'number', 'align' => 'end'],
            'headcount_budget' => ['label' => 'Budget', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Open', 'false' => 'Closed'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'title' => ['label' => 'Job title', 'required' => true, 'col' => 8, 'id' => 'position-title'],
            'grade' => ['type' => 'select', 'label' => 'Seniority band', 'col' => 4, 'required' => true,
                'value' => $record?->grade ?? 5,
                'options' => [
                    1 => '1 — Executive',
                    2 => '2 — Director',
                    3 => '3 — Manager',
                    4 => '4 — Supervisor',
                    5 => '5 — Officer',
                    6 => '6 — Support',
                ]],
            'slug' => ['label' => 'URL slug', 'slug_from' => '#position-title'],
            'description' => ['type' => 'textarea', 'label' => 'Role summary', 'rows' => 4],

            'department_id' => ['type' => 'select', 'label' => 'Department', 'panel' => 'side',
                'options' => Department::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Any department'],
            'headcount_budget' => ['label' => 'Budgeted headcount', 'input' => 'number', 'panel' => 'side',
                'min' => 0, 'help' => 'Leave blank for no limit.'],
            'min_salary' => ['label' => 'Salary band from', 'input' => 'number', 'panel' => 'side', 'min' => 0,
                'help' => 'Indicative only — actual pay is set per employee.'],
            'max_salary' => ['label' => 'Salary band to', 'input' => 'number', 'panel' => 'side', 'min' => 0],
            'is_active' => ['type' => 'checkbox', 'label' => 'Open for appointment', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    public function destroy(string|int $id): RedirectResponse
    {
        $record = $this->findForEdit($id);

        $this->authorize('delete', $record);

        if ($record->employees()->exists()) {
            return back()->with('error',
                'Employees currently hold this position. Reassign them before removing it.');
        }

        $record->delete();

        return redirect()
            ->route('admin.positions.index')
            ->with('success', 'Position archived.');
    }
}
