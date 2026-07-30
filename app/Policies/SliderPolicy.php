<?php

namespace App\Policies;

class SliderPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'sliders';
    }
}
