<?php

namespace App\Http\Controllers\Admin;

use App\Models\CsrInitiative;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CsrInitiativeController extends BaseCrudController
{
    protected string $model = CsrInitiative::class;

    protected string $viewPath = 'admin.csr-initiatives';

    protected string $routeName = 'csr-initiatives';

    protected string $label = 'CSR Initiative';

    protected string $labelPlural = 'CSR Initiatives';

    protected ?string $subtitle = 'Community and environmental programmes shown on the Corporate Responsibility page.';

    protected array $searchable = ['title', 'summary', 'focus_area', 'location'];

    protected array $imageFields = ['image' => 'csr'];

    protected array $richTextFields = ['description'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('csr_initiatives', 'slug')->ignore($model?->id)],
            'focus_area' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:600'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'location' => ['nullable', 'string', 'max:180'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'image' => ['label' => '', 'type' => 'image', 'url' => 'image_url'],
            'title' => ['label' => 'Initiative', 'type' => 'title', 'sub' => 'summary'],
            'focus_area' => ['label' => 'Focus', 'type' => 'badge', 'variant' => 'success'],
            'location' => ['label' => 'Location'],
            'year' => ['label' => 'Year'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'title' => ['label' => 'Initiative title', 'required' => true, 'col' => 8, 'id' => 'csr-title'],
            'year' => ['label' => 'Year', 'input' => 'number', 'col' => 4,
                'min' => 1990, 'max' => 2100, 'value' => $record?->year ?? now()->year],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#csr-title'],
            'focus_area' => ['label' => 'Focus area', 'col' => 6,
                'help' => 'e.g. Education, Health, Environment, Livelihoods.'],
            'location' => ['label' => 'Location'],
            'summary' => ['type' => 'textarea', 'label' => 'Summary', 'rows' => 3],
            'description' => ['type' => 'editor', 'label' => 'Full description'],

            'image' => ['type' => 'image', 'label' => 'Photograph', 'panel' => 'side', 'preview' => 'image_url'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'panel' => 'side', 'min' => 0],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }
}
