<?php

namespace App\Http\Controllers\Admin;

use App\Models\Milestone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MilestoneController extends BaseCrudController
{
    protected string $model = Milestone::class;

    protected string $viewPath = 'admin.milestones';

    protected string $routeName = 'milestones';

    protected string $label = 'Milestone';

    protected string $labelPlural = 'Company Milestones';

    protected ?string $subtitle = 'The company-history timeline on the About page.';

    protected array $searchable = ['title', 'description', 'year'];

    protected array $imageFields = ['image' => 'milestones'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'year' => ['required', 'string', 'max:10'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:800'],
            'icon' => ['required', 'string', 'max:80'],
            'image' => ['nullable', 'image', 'max:3072'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'year' => ['label' => 'Year'],
            'title' => ['label' => 'Milestone', 'type' => 'title', 'sub' => 'description'],
            'sort_order' => ['label' => 'Order', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'year' => ['label' => 'Year', 'required' => true, 'col' => 3, 'placeholder' => '2014'],
            'title' => ['label' => 'Milestone', 'required' => true, 'col' => 6],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 3, 'min' => 0],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 3],

            'icon' => ['label' => 'Font Awesome icon', 'panel' => 'side',
                'value' => $record?->icon ?? 'fa-solid fa-flag'],
            'image' => ['type' => 'image', 'label' => 'Image', 'panel' => 'side', 'preview' => 'image_url'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }
}
