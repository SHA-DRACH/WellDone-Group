<?php

namespace App\Policies;

class BudgetPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'budgets';
    }
}
