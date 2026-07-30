<?php

namespace App\Http\Controllers\Admin;

use App\Models\Division;
use App\Models\Download;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DownloadController extends BaseCrudController
{
    protected string $model = Download::class;

    protected string $viewPath = 'admin.downloads';

    protected string $routeName = 'downloads';

    protected string $label = 'Download';

    protected string $labelPlural = 'Downloads';

    protected ?string $subtitle = 'Company profile, capability statements and catalogues. A record with no file uploaded stays hidden from the public list.';

    protected array $searchable = ['title', 'description', 'category'];

    protected array $indexWith = ['division:id,name'];

    protected array $imageFields = ['thumbnail' => 'downloads/thumbnails'];

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('downloads', 'slug')->ignore($model?->id)],
            'description' => ['nullable', 'string', 'max:800'],
            'category' => ['required', 'string', 'max:60'],
            'division_id' => ['nullable', 'integer', Rule::exists('divisions', 'id')],
            'file' => [$model?->file_path ? 'nullable' : 'required', 'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip', 'max:20480'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
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
            'options' => Download::query()->distinct()->orderBy('category')->pluck('category', 'category')->all(),
        ]];
    }

    protected function columns(): array
    {
        return [
            'title' => ['label' => 'Document', 'type' => 'title', 'sub' => 'description'],
            'category' => ['label' => 'Category', 'type' => 'badge', 'variant' => 'secondary'],
            'division.name' => ['label' => 'Division'],
            'download_count' => ['label' => 'Downloads', 'type' => 'number', 'align' => 'end'],
            'is_active' => ['label' => 'Status', 'type' => 'boolean', 'true' => 'Live', 'false' => 'Hidden'],
        ];
    }

    protected function fields(?Model $record = null): array
    {
        return [
            'title' => ['label' => 'Document title', 'required' => true, 'col' => 8, 'id' => 'download-title'],
            'sort_order' => ['label' => 'Order', 'input' => 'number', 'col' => 4, 'min' => 0],
            'slug' => ['label' => 'URL slug', 'col' => 6, 'slug_from' => '#download-title'],
            'category' => ['label' => 'Category', 'col' => 6, 'required' => true,
                'value' => $record?->category ?? 'general',
                'help' => 'Groups documents on the downloads page, e.g. profile, capability, catalogue.'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 3],

            'file' => ['type' => 'file', 'label' => 'Document file', 'panel' => 'side',
                'value' => $record?->file_path,
                'accept' => '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip',
                'help' => 'PDF, Office document or ZIP, up to 20 MB.'],
            'thumbnail' => ['type' => 'image', 'label' => 'Thumbnail', 'panel' => 'side',
                'preview' => 'thumbnail_url'],
            'division_id' => ['type' => 'select', 'label' => 'Division', 'panel' => 'side',
                'options' => Division::query()->ordered()->pluck('name', 'id')->all(),
                'placeholder' => 'Group-wide'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    /**
     * The document is stored under `file` on the form but recorded across
     * three columns, so that size and original name survive for the public
     * listing without another disk lookup on every page render.
     */
    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        $data = parent::prepareData($data, $request, $record);

        unset($data['file']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $data['file_path'] = $this->uploader->document(
                $file, 'downloads', replacing: $record?->file_path
            );
            $data['file_name'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType();
            $data['size'] = $file->getSize();
        }

        return $data;
    }

    protected function beforeDelete(Model $model): void
    {
        $this->uploader->deleteFrom('public', $model->file_path);
    }
}
