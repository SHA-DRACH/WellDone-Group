<?php

namespace App\Policies;

class CertificatePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'company';
    }
}
