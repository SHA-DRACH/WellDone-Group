<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Division;
use Illuminate\Contracts\View\View;

class DivisionController extends Controller
{
    public function index(): View
    {
        seo()->set([
            'title' => 'Business Divisions — '.setting('company.name'),
            'description' => 'Mining & quarry operations, procurement & logistics, medical equipment supply and ICT services under one group.',
        ]);

        return view('site.divisions.index', [
            'divisions' => Division::query()
                ->active()
                ->ordered()
                ->withCount([
                    'services' => fn ($q) => $q->where('is_active', true),
                    'projects' => fn ($q) => $q->where('is_active', true),
                ])
                ->get(),
        ]);
    }

    public function show(Division $division): View
    {
        abort_unless($division->is_active, 404);

        $division->load([
            'seo',
            'activeServices',
            'faqs' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            'galleries' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->with('images'),
        ]);

        $projects = $division->projects()
            ->where('is_active', true)
            ->with('division:id,name,slug')
            ->ordered()
            ->limit(6)
            ->get();

        $testimonials = $division->testimonials()->where('is_active', true)->orderBy('sort_order')->limit(3)->get();

        seo()->forModel($division)->set([
            'title' => $division->name.' — '.setting('company.name'),
            'description' => $division->summary,
            'og_image' => $division->hero_image,
        ]);

        return view('site.divisions.show', compact('division', 'projects', 'testimonials'));
    }
}
