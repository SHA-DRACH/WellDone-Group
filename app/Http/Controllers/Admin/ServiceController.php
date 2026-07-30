<?php

namespace App\Http\Controllers\Admin;

use App\Models\Division;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends BaseCrudController
{
    protected string $model = Service::class;

    protected string $viewPath = 'admin.services';

    protected string $routeName = 'services';

    protected string $label = 'Service';

    protected string $labelPlural = 'Services';

    protected ?string $subtitle = 'Services appear on the division pages and become selectable line items on the quotation form.';

    protected array $searchable = ['name', 'summary'];

    protected array $indexWith = ['division:id,name'];

    protected array $imageFields = ['image' => 'services'];

    protected array $richTextFields = ['description'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('services', 'slug')->ignore($model?->id)],
            'icon' => ['required', 'string', 'max:80'],
            'summary' => ['nullable', 'string', 'max:600'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_quotable' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query->when(
            $request->query('division'),
            fn (Builder $q, $id) => $q->where('division_id', $id)
        );
    }

    protected function filters(): array
    {
        return [[
            'name' => 'division',
            'placeholder' => 'All divisions',
            'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
        ]];
    }

    protected function columns(): array
    {
        return [
            'name' => ['label' => 'Service', 'type' => 'title', 'sub' => 'summary'],
            'division.name' => ['label' => 'Division'],
            'is_quotable' => ['label' => 'Quotable', 'type' => 'boolean'],
            'is_featured' => ['label' => 'Featured', 'type' => 'boolean'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'name' => ['label' => 'Service name', 'required' => true, 'col' => 8, 'id' => 'service-name'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#service-name',
                'help' => 'Leave blank to generate from the name.'],
            'icon' => ['label' => 'Font Awesome icon', 'col' => 6,
                'value' => $record?->icon ?? 'fa-solid fa-circle-check'],
            'summary' => ['type' => 'textarea', 'label' => 'Summary', 'rows' => 3,
                'help' => 'One or two sentences, shown on service cards.'],
            'description' => ['type' => 'editor', 'label' => 'Full description'],
            'features' => ['type' => 'lines', 'label' => "What's included",
                'help' => 'One item per line.'],

            'division_id' => ['type' => 'select', 'label' => 'Division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Not assigned'],
            'image' => ['type' => 'image', 'label' => 'Image', 'panel' => 'side', 'preview' => 'image_url'],
            'is_quotable' => ['type' => 'checkbox', 'label' => 'Selectable on the quote form', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_quotable : true],
            'is_featured' => ['type' => 'checkbox', 'label' => 'Feature this service', 'panel' => 'side'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        $data = parent::prepareData($data, $request, $record);

        $data['features'] = $this->linesToArray($data['features'] ?? null);

        return $data;
    }
}
