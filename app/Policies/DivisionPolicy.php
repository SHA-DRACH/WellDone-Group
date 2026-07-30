<?php

namespace App\Policies;

class DivisionPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'divisions';
    }
}
