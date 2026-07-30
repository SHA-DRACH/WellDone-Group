<?php

namespace App\Http\Controllers\Admin;

use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogCategoryController extends BaseCrudController
{
    protected string $model = BlogCategory::class;

    protected string $viewPath = 'admin.blog-categories';

    protected string $routeName = 'blog-categories';

    protected string $label = 'Category';

    protected string $labelPlural = 'Blog Categories';

    protected ?string $subtitle = 'Sections used to group news articles.';

    protected array $searchable = ['name', 'description'];

    protected array $indexWithCount = ['blogs'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('blog_categories', 'slug')->ignore($model?->id)],
            'color' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function columns(): array
    {
        return [
            'name' => ['label' => 'Category', 'type' => 'title', 'sub' => 'description'],
            'slug' => ['label' => 'Slug'],
            'blogs_count' => ['label' => 'Articles', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'name' => ['label' => 'Category name', 'required' => true, 'col' => 8, 'id' => 'category-name'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#category-name'],
            'color' => ['label' => 'Label colour', 'input' => 'color', 'col' => 6,
                'value' => $record?->color ?? '#0b3c91'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 3],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    /**
     * A category still holding articles is not deleted — doing so would
     * silently orphan published content.
     */
    public function destroy(string|int $id): RedirectResponse
    {
        $record = $this->findForEdit($id);

        $this->authorize('delete', $record);

        if ($record->blogs()->exists()) {
            return back()->with('error', 'This category still has articles. Move or delete them first.');
        }

        $record->delete();

        return redirect()
            ->route('admin.blog-categories.index')
            ->with('success', 'Category deleted.');
    }
}
