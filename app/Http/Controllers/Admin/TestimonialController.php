<?php

namespace App\Http\Controllers\Admin;

use App\Models\Client;
use App\Models\Division;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TestimonialController extends BaseCrudController
{
    protected string $model = Testimonial::class;

    protected string $viewPath = 'admin.testimonials';

    protected string $routeName = 'testimonials';

    protected string $label = 'Testimonial';

    protected string $labelPlural = 'Testimonials';

    protected ?string $subtitle = 'Client quotations shown on the homepage and division pages.';

    protected array $searchable = ['author_name', 'company', 'quote'];

    protected array $indexWith = ['division:id,name'];

    protected array $imageFields = ['avatar' => 'testimonials'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'author_name' => ['required', 'string', 'max:150'],
            'author_title' => ['nullable', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'quote' => ['required', 'string', 'max:1200'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'avatar' => ['label' => '', 'type' => 'image', 'url' => 'avatar_url'],
            'author_name' => ['label' => 'Author', 'type' => 'title', 'sub' => 'company'],
            'quote' => ['label' => 'Quotation', 'limit' => 80],
            'division.name' => ['label' => 'Division'],
            'rating' => ['label' => 'Rating', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'author_name' => ['label' => 'Author name', 'required' => true, 'col' => 6],
            'author_title' => ['label' => 'Job title', 'col' => 6],
            'company' => ['label' => 'Organisation', 'col' => 6],
            'rating' => ['type' => 'select', 'label' => 'Rating', 'col' => 6, 'required' => true,
                'value' => $record?->rating ?? 5,
                'options' => [5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']],
            'quote' => ['type' => 'textarea', 'label' => 'Quotation', 'rows' => 5, 'required' => true,
                'help' => 'Plain text — no markup. Keep it to what the client actually said.'],

            'client_id' => ['type' => 'select', 'label' => 'Linked client', 'panel' => 'side',
                'options' => Client::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Not linked'],
            'division_id' => ['type' => 'select', 'label' => 'Division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'All divisions'],
            'avatar' => ['type' => 'image', 'label' => 'Author photo', 'panel' => 'side',
                'preview' => 'avatar_url',
                'help' => 'Optional. Initials are shown when there is no photo.'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'panel' => 'side', 'min' => 0],
            'is_featured' => ['type' => 'checkbox', 'label' => 'Feature on the homepage', 'panel' => 'side'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        $data = parent::prepareData($data, $request, $record);

        // Quotations are rendered as plain text, so strip any markup at source.
        $data['quote'] = strip_tags($data['quote']);

        return $data;
    }
}
