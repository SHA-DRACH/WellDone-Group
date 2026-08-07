<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The arithmetic behind leave.
 *
 * Every method that touches a balance does so inside a transaction with the
 * balance row locked. Two managers approving two requests at the same instant
 * is not a hypothetical — it is a Monday morning — and without the lock both
 * would read the same remaining figure and both would succeed, leaving the
 * employee overdrawn.
 *
 * The lifecycle a request moves through:
 *
 *   lodged   → days move into `pending_days` (reserved, not yet spent)
 *   approved → days move from `pending_days` into `taken_days`
 *   rejected → days leave `pending_days`; nothing is spent
 *   cancelled → days leave whichever bucket they were in
 */
class LeaveService
{
    /**
     * Working days between two dates, inclusive.
     *
     * Weekends are excluded. Public holidays are not — the company has no
     * holiday calendar in the system yet, and silently guessing at Liberian
     * public holidays would produce figures HR could not reconcile. When a
     * holiday table arrives this is the one place that needs to change.
     */
    public function workingDays(CarbonInterface $start, CarbonInterface $end, bool $halfDay = false): float
    {
        if ($end->lt($start)) {
            return 0.0;
        }

        $days = 0;
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            if (! $cursor->isWeekend()) {
                $days++;
            }

            $cursor->addDay();
        }

        // A half day is only meaningful for a single-day request.
        if ($halfDay && $days === 1) {
            return 0.5;
        }

        return (float) $days;
    }

    /**
     * The balance row for this employee, type and year, created on first use.
     *
     * Entitlement is copied from the type at creation rather than read
     * through it, so changing next year's allowance never rewrites what
     * somebody was entitled to this year.
     */
    public function balanceFor(Employee $employee, LeaveType $type, ?int $year = null): LeaveBalance
    {
        $year ??= now()->year;

        return LeaveBalance::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $year,
            ],
            ['entitled_days' => $type->days_per_year]
        );
    }

    /**
     * Why this request cannot be lodged, or null when it can.
     *
     * Returned as a message rather than thrown: every one of these is an
     * ordinary thing for a person to get wrong on a form, not an exceptional
     * condition.
     */
    public function reasonRequestIsInvalid(
        Employee $employee,
        LeaveType $type,
        CarbonInterface $start,
        CarbonInterface $end,
        float $days,
        ?LeaveRequest $ignoring = null,
    ): ?string {
        if ($days <= 0) {
            return 'That period contains no working days.';
        }

        if ($this->overlapsExistingLeave($employee, $start, $end, $ignoring)) {
            return 'You already have leave booked that overlaps these dates.';
        }

        $balance = $this->balanceFor($employee, $type, $start->year);

        if (! $balance->covers($days)) {
            return sprintf(
                'That would need %s day(s) but only %s remain of your %s entitlement.',
                rtrim(rtrim(number_format($days, 2), '0'), '.'),
                rtrim(rtrim(number_format($balance->remaining_days, 2), '0'), '.'),
                $type->name
            );
        }

        return null;
    }

    /** Any leave already booked across the same dates. */
    public function overlapsExistingLeave(
        Employee $employee,
        CarbonInterface $start,
        CarbonInterface $end,
        ?LeaveRequest $ignoring = null,
    ): bool {
        return LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->overlapping($start, $end)
            ->when($ignoring?->exists, fn ($q) => $q->whereKeyNot($ignoring->getKey()))
            ->exists();
    }

    /**
     * Lodge a request and reserve the days against the balance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function lodge(Employee $employee, LeaveType $type, array $attributes): LeaveRequest
    {
        return DB::transaction(function () use ($employee, $type, $attributes) {
            $request = new LeaveRequest($attributes);
            $request->employee_id = $employee->id;
            $request->leave_type_id = $type->id;
            $request->status = 'pending';
            $request->save();

            $this->moveDays($employee, $type, (int) $request->start_date->year, pending: (float) $request->days);

            return $request;
        });
    }

    /** Approve a pending request, converting its reserved days into taken days. */
    public function approve(LeaveRequest $request, User $reviewer, ?string $notes = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $notes) {
            $request->refresh();

            if (! $request->isPending()) {
                return;
            }

            $this->moveDays(
                $request->employee,
                $request->leaveType,
                (int) $request->start_date->year,
                pending: -(float) $request->days,
                taken: (float) $request->days,
            );

            $request->forceFill([
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ])->save();
        });
    }

    /** Refuse a pending request and release the days it was holding. */
    public function reject(LeaveRequest $request, User $reviewer, ?string $notes = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $notes) {
            $request->refresh();

            if (! $request->isPending()) {
                return;
            }

            $this->moveDays(
                $request->employee,
                $request->leaveType,
                (int) $request->start_date->year,
                pending: -(float) $request->days,
            );

            $request->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ])->save();
        });
    }

    /**
     * Withdraw a request, returning its days to whichever bucket holds them.
     */
    public function cancel(LeaveRequest $request, ?User $actor = null): void
    {
        DB::transaction(function () use ($request, $actor) {
            $request->refresh();

            if (! in_array($request->status, ['pending', 'approved'], true)) {
                return;
            }

            $this->moveDays(
                $request->employee,
                $request->leaveType,
                (int) $request->start_date->year,
                pending: $request->isPending() ? -(float) $request->days : 0.0,
                taken: $request->isApproved() ? -(float) $request->days : 0.0,
            );

            $request->forceFill([
                'status' => 'cancelled',
                'reviewed_by' => $actor?->id ?? $request->reviewed_by,
                'reviewed_at' => now(),
            ])->save();
        });
    }

    /**
     * Apply signed deltas to a balance, with the row locked for the duration.
     *
     * Callers are inside a transaction already; the lock is held until it
     * commits.
     */
    protected function moveDays(
        Employee $employee,
        LeaveType $type,
        int $year,
        float $pending = 0.0,
        float $taken = 0.0,
    ): void {
        $balance = $this->balanceFor($employee, $type, $year);

        $locked = LeaveBalance::query()->whereKey($balance->getKey())->lockForUpdate()->first();

        // Never let a rounding slip or a double-cancel push a bucket negative.
        $locked->forceFill([
            'pending_days' => max(0, (float) $locked->pending_days + $pending),
            'taken_days' => max(0, (float) $locked->taken_days + $taken),
        ])->save();
    }

    /**
     * Open a new leave year for an employee, carrying forward what each type
     * permits.
     */
    public function openYear(Employee $employee, int $year): void
    {
        $previous = $year - 1;

        foreach (LeaveType::query()->active()->get() as $type) {
            $last = LeaveBalance::query()
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $type->id)
                ->where('year', $previous)
                ->first();

            $carry = $last
                ? min((float) $type->max_carry_forward_days, max(0, $last->remaining_days))
                : 0.0;

            LeaveBalance::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                ['entitled_days' => $type->days_per_year, 'carried_forward_days' => $carry]
            );
        }
    }

    /** Parse a date the way the forms submit it. */
    public function date(string $value): Carbon
    {
        return Carbon::parse($value)->startOfDay();
    }
}
