<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'division' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'in:'.implode(',', Project::STATUSES)],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $projects = Project::query()
            ->active()
            ->with('division:id,name,slug')
            ->forDivision($validated['division'] ?? null)
            ->status($validated['status'] ?? null)
            ->search($validated['q'] ?? null)
            ->ordered()
            ->paginate((int) setting('system.items_per_page', 9))
            ->withQueryString();

        seo()->set([
            'title' => 'Projects — '.setting('company.name'),
            'description' => 'Completed and ongoing projects across mining, logistics, medical equipping and ICT.',
        ]);

        return view('site.projects.index', [
            'projects' => $projects,
            'divisions' => Division::query()->active()->ordered()->get(['id', 'name', 'slug']),
            'filters' => $validated,
        ]);
    }

    public function show(Project $project): View
    {
        abort_unless($project->is_active, 404);

        $project->load(['division', 'client', 'images', 'seo']);
        $project->recordView();

        $related = Project::query()
            ->active()
            ->where('division_id', $project->division_id)
            ->whereKeyNot($project->getKey())
            ->with('division:id,name,slug')
            ->ordered()
            ->limit(3)
            ->get();

        seo()->forModel($project)->set([
            'title' => $project->title.' — '.setting('company.name'),
            'description' => $project->summary,
            'og_image' => $project->featured_image,
        ]);

        return view('site.projects.show', compact('project', 'related'));
    }
}
