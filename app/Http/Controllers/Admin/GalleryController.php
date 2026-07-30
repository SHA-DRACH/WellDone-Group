<?php

namespace App\Http\Controllers\Admin;

use App\Models\Division;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GalleryController extends BaseCrudController
{
    protected string $model = Gallery::class;

    protected string $viewPath = 'admin.galleries';

    protected string $routeName = 'galleries';

    protected string $label = 'Gallery';

    protected string $labelPlural = 'Galleries';

    protected ?string $subtitle = 'Photo sets shown on the Gallery page and, where a division is set, on that division page.';

    protected array $searchable = ['title', 'description'];

    protected array $indexWith = ['division:id,name'];

    protected array $indexWithCount = ['images'];

    protected array $imageFields = ['cover_image' => 'galleries/covers'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('galleries', 'slug')->ignore($model?->id)],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'description' => ['nullable', 'string', 'max:800'],
            'cover_image' => ['nullable', 'image', 'max:6144'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ];
    }

    protected function applyFilters(Builder $query, Request $request): Builder
    {
        return $query->when($request->query('division'), fn (Builder $q, $id) => $q->where('division_id', $id));
    }

    protected function filters(): array
    {
        return [[
            'name' => 'division',
            'placeholder' => 'All divisions',
            'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
        ]];
    }

    protected function columns(): array
    {
        return [
            'cover_image' => ['label' => '', 'type' => 'image', 'url' => 'cover_url'],
            'title' => ['label' => 'Gallery', 'type' => 'title', 'sub' => 'description'],
            'division.name' => ['label' => 'Division'],
            'images_count' => ['label' => 'Photos', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'title' => ['label' => 'Gallery title', 'required' => true, 'col' => 8, 'id' => 'gallery-title'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'slug' => ['label' => 'URL slug', 'slug_from' => '#gallery-title'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 3],

            'cover_image' => ['type' => 'image', 'label' => 'Cover image', 'panel' => 'side',
                'preview' => 'cover_url',
                'help' => 'Optional — the first photo is used when this is blank.'],
            'division_id' => ['type' => 'select', 'label' => 'Division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Group-wide'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    protected function formData(?Model $model = null): array
    {
        return ['images' => $model?->exists ? $model->images()->get() : collect()];
    }

    // ------------------------------------------------------------------

    public function storeImages(Request $request, Gallery $gallery): RedirectResponse
    {
        $this->authorize('update', $gallery);

        $request->validate([
            'images' => ['required', 'array', 'max:40'],
            'images.*' => ['image', 'max:6144'],
        ]);

        $next = (int) $gallery->images()->max('sort_order');

        foreach ($request->file('images') as $file) {
            $gallery->images()->create([
                'path' => $this->uploader->image($file, 'galleries/'.$gallery->id),
                'sort_order' => ++$next,
            ]);
        }

        return back()->with('success', count($request->file('images')).' photo(s) added.');
    }

    public function destroyImage(GalleryImage $image): RedirectResponse
    {
        $this->authorize('update', $image->gallery);

        $image->delete();

        return back()->with('success', 'Photo removed.');
    }
}
