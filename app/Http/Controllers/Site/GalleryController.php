<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Contracts\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        seo()->set([
            'title' => 'Gallery — '.setting('company.name'),
            'description' => 'Photographs from WellDone Group operations across mining, logistics, medical equipping and ICT.',
        ]);

        return view('site.gallery.index', [
            'galleries' => Gallery::query()
                ->active()
                ->ordered()
                ->with(['division:id,name,slug', 'images'])
                ->get(),
        ]);
    }

    public function show(Gallery $gallery): View
    {
        abort_unless($gallery->is_active, 404);

        $gallery->load(['division', 'images']);

        seo()->set([
            'title' => $gallery->title.' — Gallery — '.setting('company.name'),
            'description' => $gallery->description,
            'og_image' => $gallery->cover_image,
        ]);

        return view('site.gallery.show', [
            'gallery' => $gallery,
            'others' => Gallery::query()->active()->whereKeyNot($gallery->getKey())->ordered()->limit(4)->get(),
        ]);
    }
}
