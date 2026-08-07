<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

/**
 * Entitlement rules, and the balances that follow from them.
 */
class LeaveTypePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'leave_types';
    }

    /**
     * Adjusting somebody's balance by hand — a goodwill day, a correction
     * after a payroll query. Separate from editing the rules, because it
     * changes what one named person may take.
     */
    public function adjustBalances(User $user): bool
    {
        return $user->can(Permissions::LEAVE_BALANCES_ADJUST);
    }
}
