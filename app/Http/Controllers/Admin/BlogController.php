<?php

namespace App\Http\Controllers\Admin;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends BaseCrudController
{
    protected string $model = Blog::class;

    protected string $viewPath = 'admin.blogs';

    protected string $routeName = 'blogs';

    protected string $label = 'Article';

    protected string $labelPlural = 'News & Blog';

    protected ?string $subtitle = 'Company news, project updates and commentary.';

    protected array $searchable = ['title', 'excerpt'];

    protected array $indexWith = ['category:id,name,color', 'author:id,name'];

    protected array $imageFields = ['featured_image' => 'blog'];

    protected array $richTextFields = ['body'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:220'],
            'slug' => ['nullable', 'string', 'max:240', Rule::unique('blogs', 'slug')->ignore($model?->id)],
            'blog_category_id' => ['nullable', 'integer', Rule::exists('blog_categories', 'id')],
            'excerpt' => ['nullable', 'string', 'max:600'],
            'body' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'max:6144'],
            'featured_image_alt' => ['nullable', 'string', 'max:200'],
            'status' => ['required', Rule::in(Blog::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'string', 'max:500'],
            'is_featured' => ['boolean'],
            'allow_comments' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->query('category'), fn (Builder $q, $id) => $q->where('blog_category_id', $id))
            ->when($request->query('status'), fn (Builder $q, $s) => $q->where('status', $s));
    }

    protected function applyOrder(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    protected function filters(): array
    {
        return [
            [
                'name' => 'category',
                'placeholder' => 'All categories',
                'options' => BlogCategory::query()->ordered()->pluck('name', 'id')->all(),
            ],
            [
                'name' => 'status',
                'placeholder' => 'Any status',
                'options' => collect(Blog::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all(),
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'featured_image' => ['label' => '', 'type' => 'image', 'url' => 'featured_image_url'],
            'title' => ['label' => 'Article', 'type' => 'title', 'sub' => 'excerpt'],
            'category.name' => ['label' => 'Category'],
            'author.name' => ['label' => 'Author'],
            'status' => ['label' => 'Status', 'type' => 'badge', 'variant' => 'secondary'],
            'published_at' => ['label' => 'Published', 'type' => 'date'],
            'views' => ['label' => 'Views', 'type' => 'number', 'align' => 'end'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        $canPublish = auth()->user()->can('blogs.publish');

        $statuses = collect(Blog::STATUSES)
            ->when(! $canPublish, fn ($c) => $c->reject(fn ($s) => $s === 'published'))
            ->mapWithKeys(fn ($s) => [$s => ucfirst($s)])
            ->all();

        return [
            'title' => ['label' => 'Headline', 'required' => true, 'id' => 'blog-title'],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#blog-title'],
            'featured_image_alt' => ['label' => 'Featured image description', 'col' => 6,
                'help' => 'For screen readers.'],
            'excerpt' => ['type' => 'textarea', 'label' => 'Excerpt', 'rows' => 3,
                'help' => 'Shown on cards and in search results. Generated from the body when left blank.'],
            'body' => ['type' => 'editor', 'label' => 'Article body', 'required' => true],

            'featured_image' => ['type' => 'image', 'label' => 'Featured image', 'panel' => 'side',
                'preview' => 'featured_image_url'],
            'blog_category_id' => ['type' => 'select', 'label' => 'Category', 'panel' => 'side',
                'options' => BlogCategory::query()->active()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Uncategorised'],
            'status' => ['type' => 'select', 'label' => 'Status', 'panel' => 'side', 'required' => true,
                'value' => $record?->status ?? 'draft',
                'options' => $statuses,
                'help' => $canPublish
                    ? 'Publishing with a future date schedules the article.'
                    : 'You can save drafts; an editor with publishing rights makes them live.'],
            'published_at' => ['label' => 'Publish date', 'input' => 'datetime-local', 'panel' => 'side',
                'value' => $record?->published_at?->format('Y-m-d\TH:i'),
                'help' => 'Leave blank to use the moment it is published.'],
            'tags' => ['label' => 'Topics', 'panel' => 'side',
                'value' => $record?->exists ? $record->tags->pluck('name')->implode(', ') : '',
                'help' => 'Comma-separated. New topics are created automatically.'],
            'is_featured' => ['type' => 'checkbox', 'label' => 'Feature this article', 'panel' => 'side'],
            'allow_comments' => ['type' => 'checkbox', 'label' => 'Allow comments', 'panel' => 'side',
                'value' => $record?->exists ? $record->allow_comments : true],
        ];
    }

    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        $data = parent::prepareData($data, $request, $record);

        // Tags are synced separately in afterSave().
        unset($data['tags']);

        // Someone without publishing rights cannot make an article live, even
        // by posting the value directly.
        if (! auth()->user()->can('blogs.publish') && ($data['status'] ?? null) === 'published') {
            $data['status'] = 'draft';
        }

        // The author is recorded once, on creation, and not reassigned by
        // whoever happens to edit it later.
        if (! $record) {
            $data['user_id'] = auth()->id();
        }

        return $data;
    }

    protected function afterSave(Model $model, Request $request, bool $created): void
    {
        $names = collect(explode(',', (string) $request->input('tags')))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->take(20);

        $ids = $names->map(fn (string $name) => Tag::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name]
        )->id);

        $model->tags()->sync($ids->all());
    }
}
