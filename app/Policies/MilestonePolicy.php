<?php

namespace App\Policies;

class MilestonePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'company';
    }
}
