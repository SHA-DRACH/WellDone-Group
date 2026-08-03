<?php

namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Internal announcements. These never appear on the public website — only
 * signed-in staff see them, in the portal.
 */
class AnnouncementController extends BaseCrudController
{
    protected string $model = Announcement::class;

    protected string $viewPath = 'admin.announcements';

    protected string $routeName = 'announcements';

    protected string $label = 'Announcement';

    protected string $labelPlural = 'Internal Announcements';

    protected ?string $subtitle = 'Notices for staff, shown in the employee portal. Leave both department and division blank to reach everyone.';

    protected array $searchable = ['title', 'excerpt', 'body'];

    protected array $indexWith = ['department:id,name', 'author:id,name'];

    protected array $imageFields = [];

    protected array $documentFields = ['attachment' => 'announcements'];

    protected array $richTextFields = ['body'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('announcements', 'slug')->ignore($model?->id)],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'category' => ['required', Rule::in(Announcement::CATEGORIES)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
            'is_pinned' => ['boolean'],
            'notify_by_email' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
            'is_active' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->query('category'), fn (Builder $q, $c) => $q->where('category', $c))
            ->when($request->query('department'), fn (Builder $q, $id) => $q->where('department_id', $id));
    }

    protected function applyOrder(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('published_at');
    }

    protected function filters(): array
    {
        return [
            [
                'name' => 'category',
                'placeholder' => 'All categories',
                'options' => collect(Announcement::CATEGORIES)
                    ->mapWithKeys(fn ($c) => [$c => ucfirst($c)])->all(),
            ],
            [
                'name' => 'department',
                'placeholder' => 'All audiences',
                'options' => Department::query()->ordered()->pluck('name', 'id')->all(),
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'title' => ['label' => 'Announcement', 'type' => 'title', 'sub' => 'excerpt'],
            'category' => ['label' => 'Category', 'type' => 'badge', 'color' => 'category_color'],
            'audience' => ['label' => 'Audience'],
            'author.name' => ['label' => 'Published by'],
            'published_at' => ['label' => 'Published', 'type' => 'date'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'title' => ['label' => 'Title', 'required' => true, 'id' => 'announcement-title'],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#announcement-title'],
            'excerpt' => ['type' => 'textarea', 'label' => 'Summary', 'rows' => 2,
                'help' => 'Shown in the portal list. Generated from the body when left blank.'],
            'body' => ['type' => 'editor', 'label' => 'Announcement', 'required' => true],

            'category' => ['type' => 'select', 'label' => 'Category', 'panel' => 'side', 'required' => true,
                'value' => $record?->category ?? 'general',
                'options' => collect(Announcement::CATEGORIES)
                    ->mapWithKeys(fn ($c) => [$c => ucfirst($c)])->all()],
            'department_id' => ['type' => 'select', 'label' => 'For department', 'panel' => 'side',
                'options' => Department::query()->active()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Everyone'],
            'division_id' => ['type' => 'select', 'label' => 'For division', 'panel' => 'side',
                'options' => Division::query()->active()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Everyone'],
            'attachment' => ['type' => 'file', 'label' => 'Attachment', 'panel' => 'side',
                'accept' => '.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png'],
            'published_at' => ['label' => 'Publish at', 'input' => 'datetime-local', 'panel' => 'side',
                'value' => $record?->published_at?->format('Y-m-d\TH:i'),
                'help' => 'Leave blank to publish immediately.'],
            'expires_at' => ['label' => 'Hide after', 'input' => 'datetime-local', 'panel' => 'side',
                'value' => $record?->expires_at?->format('Y-m-d\TH:i')],
            'is_pinned' => ['type' => 'checkbox', 'label' => 'Pin to the top', 'panel' => 'side'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        $data = parent::prepareData($data, $request, $record);

        // Record who published it, once.
        if (! $record) {
            $data['published_by'] = auth()->id();
        }

        return $data;
    }
}
