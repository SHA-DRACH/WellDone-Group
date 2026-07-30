<?php

namespace App\Http\Controllers\Admin;

use App\Models\Division;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FaqController extends BaseCrudController
{
    protected string $model = Faq::class;

    protected string $viewPath = 'admin.faqs';

    protected string $routeName = 'faqs';

    protected string $label = 'FAQ';

    protected string $labelPlural = 'Frequently Asked Questions';

    protected ?string $subtitle = 'Shown on the FAQs page, the contact page and — when a division is set — that division page.';

    protected array $searchable = ['question', 'answer'];

    protected array $indexWith = ['division:id,name'];

    protected array $richTextFields = ['answer'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string'],
            'category' => ['required', 'string', 'max:60'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query->when($request->query('category'), fn (Builder $q, $c) => $q->where('category', $c));
    }

    protected function filters(): array
    {
        return [[
            'name' => 'category',
            'placeholder' => 'All categories',
            'options' => Faq::query()->distinct()->orderBy('category')->pluck('category', 'category')->all(),
        ]];
    }

    protected function columns(): array
    {
        return [
            'question' => ['label' => 'Question', 'type' => 'title', 'limit' => 90],
            'category' => ['label' => 'Category', 'type' => 'badge', 'variant' => 'secondary'],
            'division.name' => ['label' => 'Division'],
            'sort_order' => ['label' => 'Order', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'question' => ['label' => 'Question', 'required' => true],
            'answer' => ['type' => 'editor', 'label' => 'Answer', 'required' => true],

            'category' => ['label' => 'Category', 'panel' => 'side', 'required' => true,
                'value' => $record?->category ?? 'general',
                'help' => 'Groups questions on the FAQs page, e.g. general, mining, logistics.'],
            'division_id' => ['type' => 'select', 'label' => 'Division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Not division-specific'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'panel' => 'side', 'min' => 0],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }
}
