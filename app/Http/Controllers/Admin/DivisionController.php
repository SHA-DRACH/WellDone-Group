<?php

namespace App\Http\Controllers\Admin;

use App\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DivisionController extends BaseCrudController
{
    protected string $model = Division::class;

    protected string $viewPath = 'admin.divisions';

    protected string $routeName = 'divisions';

    protected string $label = 'Division';

    protected string $labelPlural = 'Business Divisions';

    protected ?string $subtitle = 'Add a new subsidiary here — the website navigation, quote form and filters all follow automatically.';

    protected array $searchable = ['name', 'tagline', 'summary'];

    protected array $indexWithCount = ['services', 'projects'];

    protected array $imageFields = [
        'hero_image' => 'divisions/hero',
        'thumbnail' => 'divisions/thumbnails',
    ];

    protected array $documentFields = ['brochure_path' => 'divisions/brochures'];

    protected array $richTextFields = ['description', 'capabilities'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('divisions', 'slug')->ignore($model?->id)],
            'tagline' => ['nullable', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:80'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'capabilities' => ['nullable', 'string'],
            'highlights' => ['nullable', 'string', 'max:2000'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            'thumbnail' => ['nullable', 'image', 'max:4096'],
            'brochure_path' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'thumbnail' => ['label' => '', 'type' => 'image', 'url' => 'thumbnail_url'],
            'name' => ['label' => 'Division', 'type' => 'title', 'sub' => 'tagline'],
            'services_count' => ['label' => 'Services', 'type' => 'number', 'align' => 'end'],
            'projects_count' => ['label' => 'Projects', 'type' => 'number', 'align' => 'end'],
            'is_featured' => ['label' => 'Featured', 'type' => 'boolean'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'name' => ['label' => 'Division name', 'required' => true, 'col' => 8, 'id' => 'division-name'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0,
                'help' => 'Lower numbers appear first.'],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#division-name',
                'help' => 'Leave blank to generate from the name.'],
            'icon' => ['label' => 'Font Awesome icon', 'col' => 6,
                'value' => $record?->icon ?? 'fa-solid fa-building',
                'help' => 'e.g. fa-solid fa-mountain'],
            'tagline' => ['label' => 'Tagline', 'help' => 'One line, shown on cards and the division banner.'],
            'summary' => ['type' => 'textarea', 'label' => 'Summary', 'rows' => 3,
                'help' => 'Used in listings, search results and social previews.'],
            'description' => ['type' => 'editor', 'label' => 'Full description'],
            'capabilities' => ['type' => 'editor', 'label' => 'Capabilities'],
            'highlights' => ['type' => 'lines', 'label' => 'Key highlights',
                'help' => 'One per line. Shown as a bulleted list on the division page.'],

            'hero_image' => ['type' => 'image', 'label' => 'Banner image', 'panel' => 'side',
                'preview' => 'hero_url'],
            'thumbnail' => ['type' => 'image', 'label' => 'Card thumbnail', 'panel' => 'side',
                'preview' => 'thumbnail_url'],
            'brochure_path' => ['type' => 'file', 'label' => 'Capability statement', 'panel' => 'side',
                'accept' => '.pdf,.doc,.docx', 'help' => 'PDF or Word, up to 10 MB.'],
            'accent_color' => ['label' => 'Accent colour', 'input' => 'color', 'panel' => 'side'],
            'is_featured' => ['type' => 'checkbox', 'label' => 'Feature on the homepage', 'panel' => 'side'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        $data = parent::prepareData($data, $request, $record);

        $data['highlights'] = $this->linesToArray($data['highlights'] ?? null);

        return $data;
    }
}
