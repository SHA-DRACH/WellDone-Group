<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Expenses. Anyone with the right may submit; a claim can be changed only
 * while it is still waiting, and never approved by the person who made it.
 */
class ExpensePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'expenses';
    }

    public function update(User $user, Model $model): bool
    {
        /** @var Expense $model */
        if ($model->status !== 'pending' || ! $this->writableAcrossDivisions($user, $model)) {
            return false;
        }

        return $user->can(Permissions::EXPENSES_UPDATE)
            || ($model->submitted_by === $user->id && $user->can(Permissions::EXPENSES_CREATE));
    }

    public function delete(User $user, Model $model): bool
    {
        /** @var Expense $model */
        return $user->can(Permissions::EXPENSES_DELETE)
            && $model->status !== 'paid'
            && $this->writableAcrossDivisions($user, $model);
    }

    public function approve(User $user, Expense $expense): bool
    {
        return $user->can(Permissions::EXPENSES_APPROVE)
            && $expense->status === 'pending'
            && $expense->submitted_by !== $user->id
            && $this->writableAcrossDivisions($user, $expense);
    }

    public function markPaid(User $user, Expense $expense): bool
    {
        return ($user->can(Permissions::EXPENSES_APPROVE) || $user->can(Permissions::EXPENSES_UPDATE))
            && $expense->status === 'approved';
    }
}
