<?php

namespace App\Http\Controllers\Admin;

use App\Models\Division;
use App\Rules\VideoLength;
use App\Support\DivisionScope;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Video\Mp4Duration;
use App\Support\Video\VideoEmbed;
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
        'video_poster' => 'divisions/video-posters',
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
            'video_path' => VideoLength::rules(),
            'video_url' => ['nullable', 'url', 'max:500', function (string $attribute, mixed $value, \Closure $fail) {
                if (filled($value) && VideoEmbed::from($value) === null) {
                    $fail('Paste a YouTube or Vimeo link, e.g. https://www.youtube.com/watch?v=…');
                }
            }],
            'video_poster' => ['nullable', 'image', 'max:4096'],
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

            'video_path' => ['type' => 'video', 'label' => 'Division video (30 seconds to 1 minute)',
                'preview' => 'video_file_url', 'col' => 7],
            'video_url' => ['label' => '…or a YouTube / Vimeo link', 'input' => 'url', 'col' => 5,
                'placeholder' => 'https://www.youtube.com/watch?v=…',
                'help' => 'Used when no file is uploaded. Keep the clip between 30 seconds and 1 minute.'],

            'hero_image' => ['type' => 'image', 'label' => 'Banner image', 'panel' => 'side',
                'preview' => 'hero_url'],
            'thumbnail' => ['type' => 'image', 'label' => 'Card thumbnail', 'panel' => 'side',
                'preview' => 'thumbnail_url'],
            'brochure_path' => ['type' => 'file', 'label' => 'Capability statement', 'panel' => 'side',
                'accept' => '.pdf,.doc,.docx', 'help' => 'PDF or Word, up to 10 MB.'],
            'video_poster' => ['type' => 'image', 'label' => 'Video cover image', 'panel' => 'side',
                'preview' => 'video_poster_url',
                'help' => 'Shown before the video plays. Falls back to the banner image.'],
            'accent_color' => ['label' => 'Accent colour', 'input' => 'color', 'panel' => 'side'],
            'is_featured' => ['type' => 'checkbox', 'label' => 'Feature on the homepage', 'panel' => 'side'],
            'is_active' => ['type' => 'checkbox', 'label' => 'Published', 'panel' => 'side',
                'value' => $record?->exists ? $record->is_active : true],
        ];
    }

    /**
     * A division is its own scope: someone attached to one division sees
     * that division's entry here and not the others'.
     */
    protected function scopedQuery(): Builder
    {
        return parent::scopedQuery()
            ->when(DivisionScope::idFor(auth()->user()), fn (Builder $q, int $id) => $q->whereKey($id))
            ->when(DivisionScope::idFor(auth()->user()) === 0, fn (Builder $q) => $q->whereRaw('1 = 0'));
    }

    protected function findForEdit(string|int $id): Model
    {
        $division = parent::findForEdit($id);

        DivisionScope::assertVisible(auth()->user(), $division->id, 'division');

        return $division;
    }

    protected function prepareData(array $data, Request $request, ?Model $record): array
    {
        $data = parent::prepareData($data, $request, $record);

        $data['highlights'] = $this->linesToArray($data['highlights'] ?? null);

        return $this->prepareVideo($data, $request, $record);
    }

    /**
     * The upload is handled here rather than through $documentFields: it
     * needs its length recorded, and it is stored as a video, not a
     * document.
     */
    protected function prepareVideo(array $data, Request $request, ?Model $record): array
    {
        unset($data['video_path']);

        if ($request->boolean('remove_video_path') && $record?->video_path) {
            $this->uploader->deleteFrom('public', $record->video_path);
            $data['video_path'] = null;
            $data['video_duration'] = null;
        }

        if ($request->hasFile('video_path')) {
            $file = $request->file('video_path');
            $seconds = Mp4Duration::seconds($file->getRealPath());

            $data['video_path'] = $this->uploader->video($file, 'divisions/videos', replacing: $record?->video_path);
            $data['video_duration'] = $seconds === null ? null : (int) round($seconds);
        }

        return $data;
    }
}
