<?php

namespace App\Policies;

class CsrInitiativePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'company';
    }
}
