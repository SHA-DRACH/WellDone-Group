<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A browser for the public media disk.
 *
 * Files are listed straight from storage rather than a database table, so the
 * library always reflects what is actually on disk — including images
 * uploaded through the individual content modules.
 */
class MediaController extends Controller
{
    /** Only these directories are browsable, which also bounds deletion. */
    protected const FOLDERS = [
        'library' => 'Media library',
        'blog' => 'Blog images',
        'projects/featured' => 'Project featured images',
        'projects/gallery' => 'Project galleries',
        'galleries' => 'Photo galleries',
        'divisions/hero' => 'Division banners',
        'divisions/thumbnails' => 'Division thumbnails',
        'services' => 'Service images',
        'sliders' => 'Hero slides',
        'team' => 'Team photographs',
        'partners' => 'Partner logos',
        'clients' => 'Client logos',
        'testimonials' => 'Testimonial photos',
        'certificates/images' => 'Certificate images',
        'csr' => 'CSR photographs',
        'milestones' => 'Milestone images',
        'settings/images' => 'Settings images',
        'users/avatars' => 'User avatars',
    ];

    public function __construct(protected MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can('media.view'), 403);

        $folder = $request->query('folder', 'library');

        abort_unless(array_key_exists($folder, self::FOLDERS), 404);

        $disk = Storage::disk('public');

        $files = collect($disk->files($folder))
            ->filter(fn (string $path) => ! Str::startsWith(basename($path), '.'))
            ->when($request->query('q'), fn ($c, $term) => $c->filter(
                fn (string $path) => Str::contains(Str::lower(basename($path)), Str::lower($term))
            ))
            ->map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'url' => $disk->url($path),
                'size' => $disk->size($path),
                'modified' => $disk->lastModified($path),
                'is_image' => in_array(
                    Str::lower(pathinfo($path, PATHINFO_EXTENSION)),
                    MediaUploader::IMAGE_EXTENSIONS,
                    true
                ),
            ])
            ->sortByDesc('modified')
            ->values();

        return view('admin.media.index', [
            'files' => $files,
            'folders' => self::FOLDERS,
            'folder' => $folder,
            'search' => $request->query('q', ''),
            'totalSize' => $files->sum('size'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('media.create'), 403);

        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'max:10240'],
        ], [
            'files.*.max' => 'Each file must be no larger than 10 MB.',
        ]);

        foreach ($request->file('files') as $file) {
            $isImage = Str::startsWith((string) $file->getMimeType(), 'image/');

            $isImage
                ? $this->uploader->image($file, 'library', 2000)
                : $this->uploader->document($file, 'library');
        }

        return redirect()
            ->route('admin.media.index', ['folder' => 'library'])
            ->with('success', count($request->file('files')).' file(s) uploaded.');
    }

    /**
     * Deletion is constrained to the browsable folders, and the path is
     * re-checked against the disk listing — a crafted `path` cannot reach
     * outside the media directories.
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('media.delete'), 403);

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
        ]);

        $path = ltrim($validated['path'], '/');
        $folder = trim(dirname($path), '/');

        abort_unless(array_key_exists($folder, self::FOLDERS), 403, 'That folder cannot be modified here.');
        abort_if(Str::contains($path, '..'), 403);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $disk->delete($path);

        return back()->with('success', 'File deleted. Any page still referencing it will show a placeholder.');
    }
}
