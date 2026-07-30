<?php

namespace App\Http\Controllers\Admin;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerController extends BaseCrudController
{
    protected string $model = Partner::class;

    protected string $viewPath = 'admin.partners';

    protected string $routeName = 'partners';

    protected string $label = 'Partner';

    protected string $labelPlural = 'Partners & Accreditations';

    protected ?string $subtitle = 'Logos shown in the homepage strip — partners, suppliers, accreditation bodies and affiliations.';

    protected array $searchable = ['name', 'description'];

    protected array $imageFields = ['logo' => 'partners'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('partners', 'slug')->ignore($model?->id)],
            'type' => ['required', Rule::in(Partner::TYPES)],
            'logo' => ['nullable', 'image', 'max:2048'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query->when($request->query('type'), fn (Builder $q, $type) => $q->where('type', $type));
    }

    protected function filters(): array
    {
        return [[
            'name' => 'type',
            'placeholder' => 'All types',
            'options' => collect(Partner::TYPES)->mapWithKeys(fn ($t) => [$t => ucfirst($t)])->all(),
        ]];
    }

    protected function columns(): array
    {
        return [
            'logo' => ['label' => '', 'type' => 'image', 'url' => 'logo_url'],
            'name' => ['label' => 'Organisation', 'type' => 'title', 'sub' => 'description'],
            'type' => ['label' => 'Type', 'type' => 'badge', 'variant' => 'secondary'],
            'website' => ['label' => 'Website', 'type' => 'link', 'href' => 'website'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'name' => ['label' => 'Organisation name', 'required' => true, 'col' => 8, 'id' => 'partner-name'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#partner-name',
                'help' => 'Leave blank to generate from the name.'],
            'website' => ['label' => 'Website', 'col' => 6, 'input' => 'url',
                'placeholder' => 'https://example.com'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 3,
                'help' => 'Not shown publicly — for your own reference.'],

            'type' => ['type' => 'select', 'label' => 'Type', 'panel' => 'side', 'required' => true,
                'value' => $record?->type ?? 'partner',
                'options' => collect(Partner::TYPES)->mapWithKeys(fn ($t) => [$t => ucfirst($t)])->all()],
            'logo' => ['type' => 'image', 'label' => 'Logo', 'panel' => 'side', 'preview' => 'logo_url',
                'help' => 'Transparent PNG or SVG works best — logos are shown greyscale until hovered.'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }
}
