<?php

namespace App\Policies;

class ClientPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'clients';
    }
}
