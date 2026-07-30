<?php

namespace App\Http\Controllers\Admin;

use App\Models\Career;
use App\Models\Division;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CareerController extends BaseCrudController
{
    protected string $model = Career::class;

    protected string $viewPath = 'admin.careers';

    protected string $routeName = 'careers';

    protected string $label = 'Vacancy';

    protected string $labelPlural = 'Vacancies';

    protected ?string $subtitle = 'Job openings and internships on the careers portal. A vacancy stops accepting applications automatically after its closing date.';

    protected array $searchable = ['title', 'department', 'location'];

    protected array $indexWith = ['division:id,name'];

    protected array $indexWithCount = ['applications'];

    protected array $richTextFields = ['description', 'responsibilities', 'requirements', 'benefits'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('careers', 'slug')->ignore($model?->id)],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'department' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:180'],
            'employment_type' => ['required', Rule::in(Career::EMPLOYMENT_TYPES)],
            'experience_level' => ['required', Rule::in(Career::EXPERIENCE_LEVELS)],
            'description' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'salary_range' => ['nullable', 'string', 'max:120'],
            'positions_available' => ['required', 'integer', 'min:1', 'max:999'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->query('division'), fn (Builder $q, $id) => $q->where('division_id', $id))
            ->when($request->query('state') === 'open', fn (Builder $q) => $q->open())
            ->when($request->query('state') === 'closed', fn (Builder $q) => $q->where(
                fn (Builder $inner) => $inner->where('is_active', false)
                    ->orWhere('closes_at', '<', today())
            ));
    }

    protected function filters(): array
    {
        return [
            [
                'name' => 'division',
                'placeholder' => 'All divisions',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
            ],
            [
                'name' => 'state',
                'placeholder' => 'Any state',
                'options' => ['open' => 'Open now', 'closed' => 'Closed'],
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'title' => ['label' => 'Vacancy', 'type' => 'title', 'sub' => 'location'],
            'division.name' => ['label' => 'Division'],
            'employment_type_label' => ['label' => 'Type'],
            'applications_count' => ['label' => 'Applicants', 'type' => 'number', 'align' => 'end'],
            'closes_at' => ['label' => 'Closes', 'type' => 'date'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'title' => ['label' => 'Job title', 'required' => true, 'col' => 8, 'id' => 'career-title'],
            'positions_available' => ['label' => 'Positions', 'input' => 'number', 'col' => 4,
                'min' => 1, 'value' => $record?->positions_available ?? 1],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#career-title'],
            'location' => ['label' => 'Location', 'col' => 6, 'placeholder' => 'Monrovia'],
            'department' => ['label' => 'Department', 'col' => 6],
            'salary_range' => ['label' => 'Salary range', 'col' => 6,
                'help' => 'Free text — e.g. "Competitive" or a band.'],

            'description' => ['type' => 'editor', 'label' => 'Role overview'],
            'responsibilities' => ['type' => 'editor', 'label' => 'Key responsibilities'],
            'requirements' => ['type' => 'editor', 'label' => 'Requirements'],
            'benefits' => ['type' => 'editor', 'label' => 'What we offer'],

            'division_id' => ['type' => 'select', 'label' => 'Division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Group-level role'],
            'employment_type' => ['type' => 'select', 'label' => 'Employment type', 'panel' => 'side',
                'required' => true, 'value' => $record?->employment_type ?? 'full_time',
                'options' => collect(Career::EMPLOYMENT_TYPES)
                    ->mapWithKeys(fn ($t) => [$t => ucwords(str_replace('_', ' ', $t))])->all()],
            'experience_level' => ['type' => 'select', 'label' => 'Experience level', 'panel' => 'side',
                'required' => true, 'value' => $record?->experience_level ?? 'mid',
                'options' => collect(Career::EXPERIENCE_LEVELS)
                    ->mapWithKeys(fn ($l) => [$l => ucfirst($l)])->all()],
            'opens_at' => ['label' => 'Opens on', 'input' => 'date', 'panel' => 'side',
                'value' => $record?->opens_at?->toDateString() ?? today()->toDateString()],
            'closes_at' => ['label' => 'Closes on', 'input' => 'date', 'panel' => 'side',
                'value' => $record?->closes_at?->toDateString(),
                'help' => 'Applications close automatically after this date.'],
            'is_featured' => ['type' => 'checkbox', 'label' => 'Feature this vacancy', 'panel' => 'side'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    /**
     * Vacancies soft-delete, so applications submitted against them keep
     * their context. Warn rather than silently orphan the applications.
     */
    public function destroy(string|int $id): RedirectResponse
    {
        $record = $this->findForEdit($id);

        $this->authorize('delete', $record);

        $applications = $record->applications()->count();

        $record->delete();

        return redirect()
            ->route('admin.careers.index')
            ->with('success', $applications > 0
                ? "Vacancy archived. Its {$applications} application(s) are still available under Applications."
                : 'Vacancy archived.');
    }
}
