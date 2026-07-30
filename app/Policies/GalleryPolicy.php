<?php

namespace App\Policies;

class GalleryPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'galleries';
    }
}
