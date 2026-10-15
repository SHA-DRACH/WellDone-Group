<?php

namespace App\Policies;

/** Expense categories are part of budgeting's structure; managed with budgets. */
class ExpenseCategoryPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'budgets';
    }
}
