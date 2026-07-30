<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $divisionSlug = $request->query('division');

        seo()->set([
            'title' => 'Our Services — '.setting('company.name'),
            'description' => 'The full range of services delivered across WellDone Group\'s four business divisions.',
        ]);

        return view('site.services.index', [
            'divisions' => Division::query()
                ->active()
                ->ordered()
                ->when($divisionSlug, fn ($q) => $q->where('slug', $divisionSlug))
                ->with(['activeServices'])
                ->get(),
            'allDivisions' => Division::query()->active()->ordered()->get(['id', 'name', 'slug']),
            'activeDivision' => $divisionSlug,
        ]);
    }

    public function show(Service $service): View
    {
        abort_unless($service->is_active, 404);

        $service->load(['division', 'seo']);

        $related = Service::query()
            ->active()
            ->where('division_id', $service->division_id)
            ->whereKeyNot($service->getKey())
            ->ordered()
            ->limit(6)
            ->get();

        seo()->forModel($service)->set([
            'title' => $service->name.' — '.setting('company.name'),
            'description' => $service->summary,
            'og_image' => $service->image,
        ]);

        return view('site.services.show', compact('service', 'related'));
    }
}
