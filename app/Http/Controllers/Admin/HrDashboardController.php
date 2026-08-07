<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Career;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Support\Permissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The HR management dashboard — workforce at a glance.
 *
 * Deliberately shows only what Phase 1 can answer truthfully. Attendance and
 * leave figures belong to Phase 2; showing empty or invented tiles for them
 * would make the dashboard look broken or, worse, be believed.
 */
class HrDashboardController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can(Permissions::EMPLOYEES_VIEW), 403);

        $user = $request->user();

        /*
         * Every figure on this screen is drawn through withinDivisionOf, so a
         * division head reads their own division's headcount rather than the
         * group's. A tile is a disclosure like any other — "148 employees"
         * tells them the size of the company whether or not they can open a
         * single one of those records.
         */
        $staff = fn () => Employee::query()->withinDivisionOf($user);

        return view('admin.hr.dashboard', [
            'stats' => [
                'total' => $staff()->employed()->count(),
                'new_this_month' => $staff()
                    ->where('date_hired', '>=', now()->startOfMonth())
                    ->count(),
                'probation' => $staff()->where('employment_status', 'probation')->count(),
                'departments' => Department::query()->withinDivisionOf($user)->active()->count(),
                'pending_applications' => JobApplication::query()->withinDivisionOf($user)->open()->count(),
                'open_vacancies' => Career::query()->withinDivisionOf($user)->open()->count(),
                'birthdays_today' => $this->birthdaysToday($user),
                'contracts_expiring' => $staff()
                    ->employed()
                    ->whereNotNull('contract_expiry')
                    ->whereBetween('contract_expiry', [today(), today()->addDays(60)])
                    ->count(),
                'without_login' => $staff()->employed()->whereNull('user_id')->count(),
            ],

            'byDepartment' => Department::query()
                ->withinDivisionOf($user)
                ->active()
                ->withCount(['employees' => fn (Builder $q) => $q->employed()])
                ->ordered()
                ->get(),

            'byStatus' => $staff()
                ->select('employment_status', DB::raw('COUNT(*) as total'))
                ->groupBy('employment_status')
                ->pluck('total', 'employment_status'),

            'byGender' => $staff()
                ->employed()
                ->select('gender', DB::raw('COUNT(*) as total'))
                ->groupBy('gender')
                ->pluck('total', 'gender'),

            'recentHires' => $staff()
                ->with(['department:id,name', 'position:id,title'])
                ->whereNotNull('date_hired')
                ->orderByDesc('date_hired')
                ->limit(6)
                ->get(),

            'expiringContracts' => $staff()
                ->employed()
                ->with(['department:id,name', 'position:id,title'])
                ->whereNotNull('contract_expiry')
                ->whereBetween('contract_expiry', [today(), today()->addDays(60)])
                ->orderBy('contract_expiry')
                ->limit(6)
                ->get(),

            'birthdays' => $this->upcomingBirthdays($user),

            'recentApplications' => $user->can(Permissions::RECRUITMENT_VIEW)
                ? JobApplication::query()->withinDivisionOf($user)->with('career:id,title')->latest()->limit(6)->get()
                : collect(),

            'announcements' => Announcement::query()->withinDivisionOf($user)->live()->ordered()->limit(4)->get(),
        ]);
    }

    protected function birthdaysToday(\App\Models\User $user): int
    {
        return Employee::query()
            ->withinDivisionOf($user)
            ->employed()
            ->whereNotNull('date_of_birth')
            ->whereMonth('date_of_birth', today()->month)
            ->whereDay('date_of_birth', today()->day)
            ->count();
    }

    /**
     * Birthdays in the next fortnight.
     *
     * Compared on month and day only — the year is the person's birth year,
     * so a plain date range would never match.
     */
    protected function upcomingBirthdays(\App\Models\User $user)
    {
        $window = collect(range(0, 14))->map(fn (int $days) => today()->addDays($days));

        return Employee::query()
            ->withinDivisionOf($user)
            ->employed()
            ->whereNotNull('date_of_birth')
            ->where(function (Builder $query) use ($window) {
                foreach ($window as $day) {
                    $query->orWhere(fn (Builder $q) => $q
                        ->whereMonth('date_of_birth', $day->month)
                        ->whereDay('date_of_birth', $day->day));
                }
            })
            ->with('department:id,name')
            ->get()
            ->sortBy(fn (Employee $e) => $e->date_of_birth->setYear(today()->year)->isPast()
                ? $e->date_of_birth->setYear(today()->year + 1)
                : $e->date_of_birth->setYear(today()->year))
            ->take(6)
            ->values();
    }
}
