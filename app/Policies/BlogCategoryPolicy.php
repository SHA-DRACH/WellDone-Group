<?php

namespace App\Policies;

class BlogCategoryPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'blogs';
    }
}
