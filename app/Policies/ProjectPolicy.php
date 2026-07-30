<?php

namespace App\Policies;

class ProjectPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'projects';
    }
}
