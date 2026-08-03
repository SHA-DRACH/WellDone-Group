<?php

namespace App\Policies;

class PositionPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'positions';
    }
}
