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

        $employees = Employee::query();

        return view('admin.hr.dashboard', [
            'stats' => [
                'total' => (clone $employees)->employed()->count(),
                'new_this_month' => (clone $employees)
                    ->where('date_hired', '>=', now()->startOfMonth())
                    ->count(),
                'probation' => (clone $employees)->where('employment_status', 'probation')->count(),
                'departments' => Department::query()->active()->count(),
                'pending_applications' => JobApplication::query()->open()->count(),
                'open_vacancies' => Career::query()->open()->count(),
                'birthdays_today' => $this->birthdaysToday(),
                'contracts_expiring' => (clone $employees)
                    ->employed()
                    ->whereNotNull('contract_expiry')
                    ->whereBetween('contract_expiry', [today(), today()->addDays(60)])
                    ->count(),
                'without_login' => (clone $employees)->employed()->whereNull('user_id')->count(),
            ],

            'byDepartment' => Department::query()
                ->active()
                ->withCount(['employees' => fn (Builder $q) => $q->employed()])
                ->ordered()
                ->get(),

            'byStatus' => Employee::query()
                ->select('employment_status', DB::raw('COUNT(*) as total'))
                ->groupBy('employment_status')
                ->pluck('total', 'employment_status'),

            'byGender' => Employee::query()
                ->employed()
                ->select('gender', DB::raw('COUNT(*) as total'))
                ->groupBy('gender')
                ->pluck('total', 'gender'),

            'recentHires' => Employee::query()
                ->with(['department:id,name', 'position:id,title'])
                ->whereNotNull('date_hired')
                ->orderByDesc('date_hired')
                ->limit(6)
                ->get(),

            'expiringContracts' => Employee::query()
                ->employed()
                ->with(['department:id,name', 'position:id,title'])
                ->whereNotNull('contract_expiry')
                ->whereBetween('contract_expiry', [today(), today()->addDays(60)])
                ->orderBy('contract_expiry')
                ->limit(6)
                ->get(),

            'birthdays' => $this->upcomingBirthdays(),

            'recentApplications' => $request->user()->can(Permissions::RECRUITMENT_VIEW)
                ? JobApplication::query()->with('career:id,title')->latest()->limit(6)->get()
                : collect(),

            'announcements' => Announcement::query()->live()->ordered()->limit(4)->get(),
        ]);
    }

    protected function birthdaysToday(): int
    {
        return Employee::query()
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
    protected function upcomingBirthdays()
    {
        $window = collect(range(0, 14))->map(fn (int $days) => today()->addDays($days));

        return Employee::query()
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
