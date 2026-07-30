<?php

namespace App\Policies;

class DownloadPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'downloads';
    }
}
