<?php

namespace App\Policies;

class DepartmentPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'departments';
    }
}
