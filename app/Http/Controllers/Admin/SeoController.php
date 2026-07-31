<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seo;
use App\Services\MediaUploader;
use App\Services\SeoManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

/**
 * Per-page SEO overrides for the public site's named routes.
 *
 * Model-level SEO (a specific project or article) is edited on that record;
 * this module covers the static pages — home, about, contact and so on.
 */
class SeoController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function index(): View
    {
        abort_unless(auth()->user()->can('seo.view'), 403);

        $existing = Seo::query()->whereNotNull('route_name')->get()->keyBy('route_name');

        $pages = collect($this->manageableRoutes())
            ->map(fn (string $label, string $routeName) => [
                'route' => $routeName,
                'label' => $label,
                'url' => Route::has($routeName) ? route($routeName) : null,
                'record' => $existing->get($routeName),
            ])
            ->values();

        return view('admin.seo.index', [
            'pages' => $pages,
            'configured' => $existing->count(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('seo.update'), 403);

        $routeName = $request->query('route');

        abort_unless($routeName && array_key_exists($routeName, $this->manageableRoutes()), 404);

        $record = new Seo(['route_name' => $routeName]);

        return view('admin.seo.edit', [
            'record' => $record,
            'pageLabel' => $this->manageableRoutes()[$routeName],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('seo.update'), 403);

        $data = $request->validate($this->rules(null));

        abort_unless(array_key_exists($data['route_name'], $this->manageableRoutes()), 422);

        $data = $this->handleImage($request, $data, null);

        Seo::query()->create($data);

        SeoManager::flushCache();

        return redirect()->route('admin.seo.index')->with('success', 'SEO metadata saved.');
    }

    public function edit(Seo $seo): View
    {
        abort_unless(auth()->user()->can('seo.view'), 403);

        return view('admin.seo.edit', [
            'record' => $seo,
            'pageLabel' => $this->manageableRoutes()[$seo->route_name] ?? $seo->route_name,
        ]);
    }

    public function update(Request $request, Seo $seo): RedirectResponse
    {
        abort_unless($request->user()->can('seo.update'), 403);

        $data = $request->validate($this->rules($seo));
        $data = $this->handleImage($request, $data, $seo);

        $seo->update($data);

        SeoManager::flushCache();

        return redirect()->route('admin.seo.index')->with('success', 'SEO metadata updated.');
    }

    public function destroy(Seo $seo): RedirectResponse
    {
        abort_unless(auth()->user()->can('seo.update'), 403);

        $this->uploader->deleteFrom('public', $seo->og_image);
        $seo->delete();

        SeoManager::flushCache();

        return redirect()->route('admin.seo.index')
            ->with('success', 'Override removed — the page falls back to the site defaults.');
    }

    // ------------------------------------------------------------------

    protected function rules(?Seo $seo): array
    {
        return [
            'route_name' => [
                'required', 'string', 'max:120',
                Rule::unique('seo', 'route_name')->ignore($seo?->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'string', 'max:500'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'image', 'max:4096'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots' => ['required', 'string', 'max:60'],
        ];
    }

    protected function handleImage(Request $request, array $data, ?Seo $seo): array
    {
        unset($data['og_image']);

        if ($request->boolean('remove_og_image') && $seo) {
            $this->uploader->deleteFrom('public', $seo->og_image);
            $data['og_image'] = null;
        }

        if ($request->hasFile('og_image')) {
            $data['og_image'] = $this->uploader->image(
                $request->file('og_image'), 'seo', 1200, replacing: $seo?->og_image
            );
        }

        return $data;
    }

    /**
     * Static public pages that can carry an override. Detail pages are not
     * listed — their metadata belongs on the record itself.
     *
     * @return array<string, string>
     */
    protected function manageableRoutes(): array
    {
        return [
            'site.home' => 'Home',
            'site.about' => 'About Us',
            'site.leadership' => 'Leadership & Team',
            'site.csr' => 'Corporate Responsibility',
            'site.certifications' => 'Certifications',
            'site.divisions.index' => 'Business Divisions',
            'site.services.index' => 'Services',
            'site.projects.index' => 'Projects',
            'site.gallery.index' => 'Gallery',
            'site.blog.index' => 'News & Blog',
            'site.careers.index' => 'Careers',
            'site.downloads.index' => 'Downloads',
            'site.faqs' => 'FAQs',
            'site.contact' => 'Contact Us',
            'site.quote.create' => 'Request a Quote',
        ];
    }
}
