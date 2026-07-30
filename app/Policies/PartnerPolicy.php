<?php

namespace App\Policies;

class PartnerPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'partners';
    }
}
