<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;

/**
 * Who may see and decide a leave request.
 *
 * Two rules sit on top of the plain permission check:
 *
 *  - Everyone can always see their own requests, permission or not. Leave you
 *    applied for yourself is not confidential from you.
 *  - Nobody approves their own leave, however senior. A manager who could
 *    sign off their own absence is not a control at all.
 */
class LeaveRequestPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'leave';
    }

    public function view(User $user, Model $model): bool
    {
        /** @var LeaveRequest $model */
        if ($this->isOwn($user, $model)) {
            return true;
        }

        if (! $user->can(Permissions::LEAVE_VIEW)) {
            return false;
        }

        return $this->withinScope($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        /** @var LeaveRequest $model */
        if (! $model->isPending()) {
            return false;
        }

        if (! $user->can(Permissions::LEAVE_UPDATE)) {
            return false;
        }

        return $this->withinScope($user, $model);
    }

    /** Granting or refusing time off. */
    public function decide(User $user, LeaveRequest $request): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        // Never your own, whatever you hold.
        if ($this->isOwn($user, $request)) {
            return false;
        }

        if (! $user->can(Permissions::LEAVE_APPROVE)) {
            return false;
        }

        return $this->withinScope($user, $request);
    }

    /**
     * Withdrawing a request: the employee may withdraw their own until it
     * starts; HR may withdraw anyone's.
     */
    public function cancel(User $user, LeaveRequest $request): bool
    {
        if (! $request->isCancellable()) {
            return false;
        }

        return $this->isOwn($user, $request) || $user->can(Permissions::LEAVE_UPDATE);
    }

    protected function isOwn(User $user, LeaveRequest $request): bool
    {
        return $user->employee?->id === $request->employee_id;
    }

    /**
     * A department manager is confined to their own department. HR and group
     * roles see everyone.
     */
    protected function withinScope(User $user, LeaveRequest $request): bool
    {
        if (! $user->hasRole(Roles::DEPARTMENT_MANAGER)) {
            return true;
        }

        $department = $user->employee?->department_id;

        return $department !== null
            && $request->employee?->department_id === $department;
    }
}
