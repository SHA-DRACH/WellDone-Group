<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Career;
use App\Models\ContactMessage;
use App\Models\Division;
use App\Models\JobApplication;
use App\Models\Newsletter;
use App\Models\PageView;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        /*
         * This screen counts the whole group: every division's requests,
         * projects and staff. Somebody confined to one division has no
         * business reading it, and the sign-in redirect alone is not enough —
         * /admin can be typed, bookmarked, or reached by a stale link.
         *
         * Checked before the permission, so a division user without the
         * dashboard permission is sent home rather than shown a 403.
         */
        if (Navigation::isDivisionScoped(auth()->user())) {
            return redirect()->to(Navigation::homeRouteFor(auth()->user()));
        }

        // The dashboard is its own capability — it must not be tied to any
        // one module's permissions, or a sales officer would be locked out.
        abort_unless(auth()->user()->can(\App\Support\Permissions::DASHBOARD_VIEW), 403);

        return view('admin.dashboard', [
            'stats' => $this->statistics(),
            'traffic' => $this->traffic(),
            'recentQuotes' => $this->recentQuotes(),
            'recentMessages' => $this->recentMessages(),
            'recentApplications' => $this->recentApplications(),
            'activity' => $this->recentActivity(),
            'quoteBreakdown' => $this->quoteBreakdown(),
        ]);
    }

    /** @return array<string, mixed> */
    protected function statistics(): array
    {
        return [
            'quotes_new' => QuoteRequest::query()->where('status', 'new')->count(),
            'quotes_open' => QuoteRequest::query()->open()->count(),
            'quotes_total' => QuoteRequest::query()->count(),
            'messages_new' => ContactMessage::query()->where('status', 'new')->count(),
            'applications_new' => JobApplication::query()->where('status', 'new')->count(),
            'applications_total' => JobApplication::query()->count(),
            'projects' => Project::query()->where('is_active', true)->count(),
            'projects_ongoing' => Project::query()->where('status', 'ongoing')->count(),
            'services' => Service::query()->where('is_active', true)->count(),
            'divisions' => Division::query()->where('is_active', true)->count(),
            'posts_published' => Blog::query()->published()->count(),
            'posts_draft' => Blog::query()->where('status', 'draft')->count(),
            'careers_open' => Career::query()->open()->count(),
            'subscribers' => Newsletter::query()->subscribed()->count(),
            'users' => User::query()->where('is_active', true)->count(),
        ];
    }

    /**
     * Page views and unique visitors for the last 30 days, shaped for the
     * inline SVG sparkline on the dashboard.
     *
     * @return array<string, mixed>
     */
    protected function traffic(): array
    {
        $since = now()->subDays(29)->startOfDay();

        $rows = PageView::query()
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_hash) as visitors')
            ->where('viewed_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy(fn ($row) => (string) $row->day);

        $series = [];

        for ($date = $since->copy(); $date->lte(now()); $date->addDay()) {
            $key = $date->toDateString();
            $row = $rows->get($key);

            $series[] = [
                'date' => $date->copy(),
                'views' => (int) ($row->views ?? 0),
                'visitors' => (int) ($row->visitors ?? 0),
            ];
        }

        return [
            'series' => $series,
            'views_total' => array_sum(array_column($series, 'views')),
            'visitors_total' => array_sum(array_column($series, 'visitors')),
            'peak' => max(1, max(array_column($series, 'views'))),
            'top_pages' => PageView::query()
                ->selectRaw('path, COUNT(*) as hits')
                ->where('viewed_at', '>=', $since)
                ->groupBy('path')
                ->orderByDesc('hits')
                ->limit(8)
                ->get(),
        ];
    }

    protected function quoteBreakdown()
    {
        return QuoteRequest::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    protected function recentQuotes()
    {
        return auth()->user()->can('quotes.view')
            ? QuoteRequest::query()->with('division:id,name')->latest()->limit(6)->get()
            : collect();
    }

    protected function recentMessages()
    {
        return auth()->user()->can('contact.view')
            ? ContactMessage::query()->latest()->limit(6)->get()
            : collect();
    }

    protected function recentApplications()
    {
        return auth()->user()->can('applications.view')
            ? JobApplication::query()->with('career:id,title')->latest()->limit(6)->get()
            : collect();
    }

    protected function recentActivity()
    {
        return auth()->user()->can('activity.view')
            ? Activity::query()->with('causer')->latest()->limit(12)->get()
            : collect();
    }
}
