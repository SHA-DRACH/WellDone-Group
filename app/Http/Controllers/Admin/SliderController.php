<?php

namespace App\Http\Controllers\Admin;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SliderController extends BaseCrudController
{
    protected string $model = Slider::class;

    protected string $viewPath = 'admin.sliders';

    protected string $routeName = 'sliders';

    protected string $label = 'Hero Slide';

    protected string $labelPlural = 'Hero Slides';

    protected ?string $subtitle = 'The full-screen slider at the top of the homepage.';

    protected array $searchable = ['title', 'eyebrow', 'description'];

    protected array $imageFields = ['image' => 'sliders'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'highlight' => ['nullable', 'string', 'max:180'],
            'slogan' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:600'],
            // Required on create, optional when editing an existing slide.
            'image' => [$model ? 'nullable' : 'required', 'image', 'max:6144'],
            'image_alt' => ['nullable', 'string', 'max:200'],
            'primary_button_text' => ['nullable', 'string', 'max:60'],
            'primary_button_url' => ['nullable', 'string', 'max:255'],
            'secondary_button_text' => ['nullable', 'string', 'max:60'],
            'secondary_button_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'image' => ['label' => '', 'type' => 'image', 'url' => 'image_url'],
            'title' => ['label' => 'Slide', 'type' => 'title', 'sub' => 'slogan'],
            'eyebrow' => ['label' => 'Eyebrow'],
            'sort_order' => ['label' => 'Order', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'eyebrow' => ['label' => 'Eyebrow', 'col' => 6,
                'help' => 'Small label above the heading, e.g. the division name.'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 6, 'min' => 0],
            'title' => ['label' => 'Heading', 'required' => true, 'col' => 7],
            'highlight' => ['label' => 'Highlighted second line', 'col' => 5,
                'help' => 'Rendered in gold beneath the heading.'],
            'slogan' => ['label' => 'Slogan'],
            'description' => ['type' => 'textarea', 'label' => 'Supporting text', 'rows' => 3],

            'primary_button_text' => ['label' => 'Primary button label', 'col' => 6],
            'primary_button_url' => ['label' => 'Primary button link', 'col' => 6,
                'placeholder' => '/request-a-quote'],
            'secondary_button_text' => ['label' => 'Secondary button label', 'col' => 6],
            'secondary_button_url' => ['label' => 'Secondary button link', 'col' => 6,
                'placeholder' => '/services'],

            'image' => ['type' => 'image', 'label' => 'Background image', 'panel' => 'side',
                'preview' => 'image_url',
                'help' => 'Landscape, at least 1600px wide. Text sits over a dark overlay.'],
            'image_alt' => ['label' => 'Image description', 'panel' => 'side',
                'help' => 'For screen readers. Leave blank if the image is decorative.'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Show this slide', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }
}
