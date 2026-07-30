<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Models\Division;
use App\Models\JobApplication;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * A per-division dashboard.
 *
 * Everyone signs in through the same form; division staff are routed here
 * instead of the group control panel. The screen is scoped to one division:
 * its quote requests, projects, services, vacancies and applications.
 *
 * Group-level staff may open any division's dashboard; division staff may
 * only open their own — enforced in authorise() below, not just hidden in
 * the navigation.
 */
class DivisionDashboardController extends Controller
{
    public function index(Request $request, Division $division): View
    {
        $this->authorizeDivision($division);

        $quotes = QuoteRequest::query()->where('division_id', $division->id);

        $stats = [
            'requests_new' => (clone $quotes)->where('status', 'new')->count(),
            'requests_open' => (clone $quotes)->open()->count(),
            'requests_won' => (clone $quotes)->where('status', 'won')->count(),
            'requests_total' => (clone $quotes)->count(),
            'requests_month' => (clone $quotes)->where('created_at', '>=', now()->startOfMonth())->count(),
            'quoted_value' => (clone $quotes)->whereNotNull('quoted_amount')->sum('quoted_amount'),
            'projects_ongoing' => Project::query()->where('division_id', $division->id)->where('status', 'ongoing')->count(),
            'projects_total' => Project::query()->where('division_id', $division->id)->where('is_active', true)->count(),
            'services' => Service::query()->where('division_id', $division->id)->where('is_active', true)->count(),
            'vacancies' => Career::query()->where('division_id', $division->id)->open()->count(),
            'applications_new' => JobApplication::query()
                ->whereHas('career', fn ($q) => $q->where('division_id', $division->id))
                ->where('status', 'new')
                ->count(),
            'team' => User::query()->where('division_id', $division->id)->where('is_active', true)->count(),
        ];

        return view('admin.division-dashboard', [
            'division' => $division,
            'stats' => $stats,
            'statusBreakdown' => (clone $quotes)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status'),
            'recentRequests' => (clone $quotes)
                ->with(['services:id,name', 'assignee:id,name'])
                ->latest()
                ->limit(10)
                ->get(),
            'topServices' => Service::query()
                ->where('division_id', $division->id)
                ->withCount('quoteRequests')
                ->orderByDesc('quote_requests_count')
                ->limit(6)
                ->get(),
            'projects' => Project::query()
                ->where('division_id', $division->id)
                ->whereIn('status', ['ongoing', 'upcoming'])
                ->orderBy('end_date')
                ->limit(6)
                ->get(),
            'applications' => JobApplication::query()
                ->whereHas('career', fn ($q) => $q->where('division_id', $division->id))
                ->with('career:id,title')
                ->latest()
                ->limit(5)
                ->get(),
            'switchable' => $this->switchableDivisions(),
            'quickLinks' => $this->quickLinks(),
        ]);
    }

    /**
     * Side-panel shortcuts, filtered to the admin modules that are actually
     * mounted. The modules are being rolled out incrementally, and linking to
     * one that is not registered yet would fail the whole page render.
     *
     * @return array<int, array<string, string>>
     */
    protected function quickLinks(): array
    {
        $candidates = [
            ['route' => 'admin.quotes.index', 'can' => 'quotes.view', 'icon' => 'fa-file-invoice-dollar', 'label' => 'All requests for this division'],
            ['route' => 'admin.projects.index', 'can' => 'projects.view', 'icon' => 'fa-diagram-project', 'label' => 'Projects'],
            ['route' => 'admin.services.index', 'can' => 'services.view', 'icon' => 'fa-list-check', 'label' => 'Services'],
            ['route' => 'admin.careers.index', 'can' => 'careers.view', 'icon' => 'fa-briefcase', 'label' => 'Vacancies'],
            ['route' => 'admin.galleries.index', 'can' => 'galleries.view', 'icon' => 'fa-images', 'label' => 'Galleries'],
        ];

        return array_values(array_filter(
            $candidates,
            fn (array $link) => Route::has($link['route'])
        ));
    }

    /**
     * Division staff are confined to their own division; group roles may
     * view any. A deactivated division is only reachable by group staff.
     */
    protected function authorizeDivision(Division $division): void
    {
        $user = auth()->user();

        if (! Navigation::isDivisionScoped($user)) {
            return;
        }

        abort_unless($user->division_id === $division->id, 403,
            'You only have access to your own division dashboard.');

        abort_unless($division->is_active, 404);
    }

    /** Divisions the signed-in user may switch between. */
    protected function switchableDivisions()
    {
        $user = auth()->user();

        if (Navigation::isDivisionScoped($user)) {
            return collect();
        }

        return Division::query()->active()->ordered()->get(['id', 'name', 'slug', 'icon']);
    }
}
