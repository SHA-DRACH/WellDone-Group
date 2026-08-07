<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Notifications\LeaveRequestDecided;
use App\Services\LeaveService;
use App\Support\Navigation;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The HR side of leave: reviewing what has been applied for, and deciding it.
 *
 * A department manager sees only their own department. That is applied to the
 * query as well as the policy — a manager should not be able to page through
 * the whole company's sick leave by editing a URL.
 */
class LeaveRequestController extends Controller
{
    public function __construct(protected LeaveService $leave) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $requests = $this->scopedQuery()
            ->with(['employee:id,first_name,last_name,employee_number,department_id,photo', 'leaveType:id,name,colour'])
            ->when($request->query('status'), fn (Builder $q, $s) => $q->where('status', $s))
            ->when($request->query('type'), fn (Builder $q, $id) => $q->where('leave_type_id', $id))
            ->when($request->query('department'), fn (Builder $q, $id) => $q->whereHas(
                'employee', fn (Builder $e) => $e->where('department_id', $id)
            ))
            ->when($request->query('q'), fn (Builder $q, $term) => $q->where(function (Builder $inner) use ($term) {
                $inner->where('reference', 'like', "%{$term}%")
                    ->orWhereHas('employee', fn (Builder $e) => $e->search($term));
            }))
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('admin.leave.index', [
            'records' => $requests,
            'search' => $request->query('q', ''),
            'types' => LeaveType::query()->ordered()->pluck('name', 'id'),
            'departments' => Department::query()->ordered()->pluck('name', 'id'),
            'counts' => $this->scopedQuery()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'onLeaveToday' => $this->scopedQuery()->current()->count(),
        ]);
    }

    public function show(LeaveRequest $leave): View
    {
        $this->authorize('view', $leave);

        $leave->load([
            'employee.department', 'employee.position', 'leaveType',
            'reviewer:id,name', 'coveringEmployee:id,first_name,last_name',
        ]);

        return view('admin.leave.show', [
            'record' => $leave,
            'balance' => $this->leave->balanceFor(
                $leave->employee, $leave->leaveType, (int) $leave->start_date->year
            ),
            // What else is off across the same dates, so a manager can see the
            // cover situation before granting.
            'clashes' => LeaveRequest::query()
                ->approved()
                ->whereKeyNot($leave->getKey())
                ->whereHas('employee', fn (Builder $q) => $q->where('department_id', $leave->employee->department_id))
                ->overlapping($leave->start_date, $leave->end_date)
                ->with('employee:id,first_name,last_name')
                ->get(),
        ]);
    }

    /** Grant or refuse. */
    public function decide(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('decide', $leave);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // A refusal without a word of explanation is not a decision anyone can
        // act on, so it is required here and optional for an approval.
        if ($data['decision'] === 'reject' && blank($data['review_notes'] ?? null)) {
            return back()->withErrors(['review_notes' => 'Please give a reason for refusing this request.']);
        }

        $reviewer = $request->user();

        $data['decision'] === 'approve'
            ? $this->leave->approve($leave, $reviewer, $data['review_notes'] ?? null)
            : $this->leave->reject($leave, $reviewer, $data['review_notes'] ?? null);

        $leave->refresh();

        $leave->employee->user?->notify(new LeaveRequestDecided($leave->fresh('leaveType')));

        return back()->with('success', $data['decision'] === 'approve'
            ? 'Leave approved. '.$leave->employee->first_name.' has been notified.'
            : 'Leave refused. '.$leave->employee->first_name.' has been notified.');
    }

    /** Withdraw a request on the employee's behalf. */
    public function cancel(Request $request, LeaveRequest $leave): RedirectResponse
    {
        $this->authorize('cancel', $leave);

        $this->leave->cancel($leave, $request->user());

        return back()->with('success', 'Request withdrawn and the days returned to the balance.');
    }

    /**
     * Lodge a request on somebody's behalf — the paper form that arrives at
     * the HR desk.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LeaveRequest::class);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'leave_type_id' => ['required', 'integer', Rule::exists('leave_types', 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $employee = Employee::query()->findOrFail($data['employee_id']);
        $type = LeaveType::query()->findOrFail($data['leave_type_id']);

        $start = $this->leave->date($data['start_date']);
        $end = $this->leave->date($data['end_date']);
        $days = $this->leave->workingDays($start, $end, $request->boolean('is_half_day'));

        if ($problem = $this->leave->reasonRequestIsInvalid($employee, $type, $start, $end, $days)) {
            return back()->withInput()->withErrors(['start_date' => $problem]);
        }

        $leave = $this->leave->lodge($employee, $type, [
            'start_date' => $start,
            'end_date' => $end,
            'days' => $days,
            'is_half_day' => $request->boolean('is_half_day'),
            'reason' => $data['reason'] ?? null,
        ]);

        return redirect()
            ->route('admin.leave.show', $leave)
            ->with('success', 'Request lodged as '.$leave->reference.'.');
    }

    /** Narrowed to what this user may see. */
    protected function scopedQuery(): Builder
    {
        $query = LeaveRequest::query();
        $user = auth()->user();

        if ($user->hasRole(Roles::DEPARTMENT_MANAGER) && ! $user->hasAnyRole(Navigation::GROUP_ROLES)) {
            $department = $user->employee?->department_id ?? 0;

            $query->whereHas('employee', fn (Builder $q) => $q->where('department_id', $department));
        }

        return $query;
    }
}
