<?php

namespace App\Policies;

class CareerPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'careers';
    }
}
