<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;

/**
 * Employee records hold the most sensitive data in the application, so
 * viewing the record, seeing pay, and seeing identity documents are three
 * separate rights rather than one.
 */
class EmployeePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'employees';
    }

    /**
     * A department manager sees only their own department's staff. Everyone
     * can always see their own record.
     */
    public function view(User $user, Model $model): bool
    {
        /** @var Employee $model */
        if ($model->user_id === $user->id) {
            return true;
        }

        if (! $user->can(Permissions::EMPLOYEES_VIEW)) {
            return false;
        }

        return $this->withinScope($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        if (! $user->can(Permissions::EMPLOYEES_UPDATE)) {
            return false;
        }

        /** @var Employee $model */
        return $this->withinScope($user, $model);
    }

    /** Salary and bank details. */
    public function viewSalary(User $user, ?Employee $employee = null): bool
    {
        return $user->can(Permissions::EMPLOYEES_VIEW_SALARY);
    }

    public function updateSalary(User $user, ?Employee $employee = null): bool
    {
        return $user->can(Permissions::EMPLOYEES_UPDATE_SALARY);
    }

    /** National ID, passport number, date of birth. */
    public function viewSensitive(User $user, ?Employee $employee = null): bool
    {
        return $user->can(Permissions::EMPLOYEES_VIEW_SENSITIVE);
    }

    /**
     * A department manager is confined to their own department; HR and group
     * roles see everyone.
     */
    protected function withinScope(User $user, Employee $employee): bool
    {
        if (! $user->hasRole(Roles::DEPARTMENT_MANAGER)) {
            return true;
        }

        $managerDepartment = $user->employee?->department_id;

        return $managerDepartment !== null && $employee->department_id === $managerDepartment;
    }
}
