<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\Division;
use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends BaseCrudController
{
    protected string $model = Project::class;

    protected string $viewPath = 'admin.projects';

    protected string $routeName = 'projects';

    protected string $label = 'Project';

    protected string $labelPlural = 'Projects';

    protected ?string $subtitle = 'Completed and ongoing work shown in the public portfolio.';

    protected array $searchable = ['title', 'summary', 'location'];

    protected array $indexWith = ['division:id,name', 'client:id,name'];

    protected array $imageFields = ['featured_image' => 'projects/featured'];

    protected array $richTextFields = ['description', 'scope_of_work'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('projects', 'slug')->ignore($model?->id)],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'summary' => ['nullable', 'string', 'max:800'],
            'description' => ['nullable', 'string'],
            'scope_of_work' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'image', 'max:6144'],
            'location' => ['nullable', 'string', 'max:180'],
            'client_display_name' => ['nullable', 'string', 'max:180'],
            'show_client' => ['boolean'],
            'contract_value' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'show_value' => ['boolean'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'completion_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'key_facts' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->query('division'), fn (Builder $q, $id) => $q->where('division_id', $id))
            ->when($request->query('status'), fn (Builder $q, $s) => $q->where('status', $s));
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
                'name' => 'status',
                'placeholder' => 'Any status',
                'options' => collect(Project::STATUSES)
                    ->mapWithKeys(fn ($s) => [$s => ucwords(str_replace('_', ' ', $s))])->all(),
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'featured_image' => ['label' => '', 'type' => 'image', 'url' => 'featured_image_url'],
            'title' => ['label' => 'Project', 'type' => 'title', 'sub' => 'location'],
            'division.name' => ['label' => 'Division'],
            'status_label' => ['label' => 'Status', 'type' => 'badge', 'variant' => 'secondary'],
            'views' => ['label' => 'Views', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Published', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'title' => ['label' => 'Project title', 'required' => true, 'col' => 8, 'id' => 'project-title'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#project-title'],
            'location' => ['label' => 'Location', 'col' => 6],
            'summary' => ['type' => 'textarea', 'label' => 'Summary', 'rows' => 3,
                'help' => 'Shown on project cards, search results and social previews.'],
            'description' => ['type' => 'editor', 'label' => 'Description'],
            'scope_of_work' => ['type' => 'editor', 'label' => 'Scope of work'],
            'key_facts' => ['type' => 'pairs', 'label' => 'Key facts',
                'help' => 'One per line, in the form `Label: value` — e.g. `Material supplied: 180,000 m³`.'],

            'featured_image' => ['type' => 'image', 'label' => 'Featured image', 'panel' => 'side',
                'preview' => 'featured_image_url'],
            'division_id' => ['type' => 'select', 'label' => 'Division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Not assigned'],
            'client_id' => ['type' => 'select', 'label' => 'Client', 'panel' => 'side',
                'options' => Client::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Not linked'],
            'client_display_name' => ['label' => 'Client label override', 'panel' => 'side',
                'help' => 'Used when no client is linked, or the client may not be named.'],
            'show_client' => ['type' => 'checkbox', 'label' => 'Show the client publicly', 'panel' => 'side',
                'value' => $record?->exists ? $record->show_client : true],
            'status' => ['type' => 'select', 'label' => 'Status', 'panel' => 'side', 'required' => true,
                'value' => $record?->status ?? 'completed',
                'options' => collect(Project::STATUSES)
                    ->mapWithKeys(fn ($s) => [$s => ucwords(str_replace('_', ' ', $s))])->all()],
            'completion_percentage' => ['label' => 'Completion %', 'input' => 'number', 'panel' => 'side',
                'required' => true, 'min' => 0, 'max' => 100,
                'value' => $record?->exists ? $record->completion_percentage : 100],
            'start_date' => ['label' => 'Start date', 'input' => 'date', 'panel' => 'side',
                'value' => $record?->start_date?->toDateString()],
            'end_date' => ['label' => 'End date', 'input' => 'date', 'panel' => 'side',
                'value' => $record?->end_date?->toDateString()],
            'contract_value' => ['label' => 'Contract value', 'input' => 'number', 'panel' => 'side', 'min' => 0],
            'currency' => ['type' => 'select', 'label' => 'Currency', 'panel' => 'side', 'required' => true,
                'value' => $record?->currency ?? 'USD',
                'options' => ['USD' => 'USD', 'LRD' => 'LRD', 'EUR' => 'EUR', 'GBP' => 'GBP']],
            'show_value' => ['type' => 'checkbox', 'label' => 'Show contract value publicly', 'panel' => 'side'],
            'is_featured' => ['type' => 'checkbox', 'label' => 'Feature on the homepage', 'panel' => 'side'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    protected function formData(?Model $model = null): array
    {
        return ['images' => $model?->exists ? $model->images()->get() : collect()];
    }

    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        $data = parent::prepareData($data, $request, $record);

        $data['key_facts'] = $this->linesToPairs($data['key_facts'] ?? null);

        return $data;
    }

    // ------------------------------------------------------------------
    // Gallery images
    // ------------------------------------------------------------------

    public function storeImages(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['image', 'max:6144'],
        ], [
            'images.*.image' => 'Every gallery upload must be an image.',
            'images.*.max' => 'Each image must be no larger than 6 MB.',
        ]);

        $next = (int) $project->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            $project->images()->create([
                'path' => $this->uploader->image($file, 'projects/gallery/'.$project->id),
                'sort_order' => ++$next,
            ]);
        }

        return back()->with('success', count($request->file('images')).' image(s) added to the gallery.');
    }

    public function destroyImage(ProjectImage $image): RedirectResponse
    {
        $this->authorize('update', $image->project);

        // The model's deleted hook removes the file from disk.
        $image->delete();

        return back()->with('success', 'Image removed.');
    }
}
