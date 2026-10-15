<?php

namespace App\Policies;

use App\Models\PayrollRun;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Payroll. Preparing and approving are different people: the policy refuses
 * approval to whoever prepared the run, so no one can both set and sign off
 * their own figures. (A super administrator is exempt, via Gate::before.)
 */
class PayrollRunPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'payroll';
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::PAYROLL_PROCESS);
    }

    public function update(User $user, Model $model): bool
    {
        /** @var PayrollRun $model */
        return $user->can(Permissions::PAYROLL_PROCESS) && $model->isDraft();
    }

    public function delete(User $user, Model $model): bool
    {
        /** @var PayrollRun $model */
        return $user->can(Permissions::PAYROLL_PROCESS) && $model->isDraft();
    }

    public function approve(User $user, PayrollRun $run): bool
    {
        return $user->can(Permissions::PAYROLL_APPROVE)
            && $run->isDraft()
            && $run->employee_count > 0
            && $run->prepared_by !== $user->id;
    }

    public function markPaid(User $user, PayrollRun $run): bool
    {
        return ($user->can(Permissions::PAYROLL_PROCESS) || $user->can(Permissions::PAYROLL_APPROVE))
            && $run->status === 'approved';
    }
}
