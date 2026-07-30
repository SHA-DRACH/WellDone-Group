<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends BaseCrudController
{
    protected string $model = Client::class;

    protected string $viewPath = 'admin.clients';

    protected string $routeName = 'clients';

    protected string $label = 'Client';

    protected string $labelPlural = 'Clients';

    protected ?string $subtitle = 'Organisations we have delivered for. Clear "may be named publicly" where a contract forbids attribution.';

    protected array $searchable = ['name', 'industry', 'country'];

    protected array $indexWithCount = ['projects'];

    protected array $imageFields = ['logo' => 'clients'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:200', Rule::unique('clients', 'slug')->ignore($model?->id)],
            'industry' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'website' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'logo' => ['label' => '', 'type' => 'image', 'url' => 'logo_url'],
            'name' => ['label' => 'Client', 'type' => 'title', 'sub' => 'industry'],
            'country' => ['label' => 'Country'],
            'projects_count' => ['label' => 'Projects', 'type' => 'number', 'align' => 'end'],
            'is_public' => ['label' => 'Nameable', 'type' => 'boolean', 'true' => 'Public', 'false' => 'Confidential'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Active', 'false' => 'Archived'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'name' => ['label' => 'Organisation name', 'required' => true, 'col' => 8, 'id' => 'client-name'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#client-name'],
            'website' => ['label' => 'Website', 'col' => 6, 'input' => 'url'],
            'industry' => ['label' => 'Industry', 'col' => 6],
            'country' => ['label' => 'Country', 'col' => 6, 'value' => $record?->country ?? 'Liberia'],
            'notes' => ['type' => 'textarea', 'label' => 'Internal notes', 'rows' => 4,
                'help' => 'Never shown publicly.'],

            'logo' => ['type' => 'image', 'label' => 'Logo', 'panel' => 'side', 'preview' => 'logo_url'],
            'is_public' => ['type' => 'checkbox', 'label' => 'May be named publicly', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_public : true,
                'help' => 'Uncheck where the contract prohibits attribution. Projects then show the confidential label instead.'],
            'is_featured' => ['type' => 'checkbox', 'label' => 'Show in the client logo strip', 'panel' => 'side'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Active', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }
}
