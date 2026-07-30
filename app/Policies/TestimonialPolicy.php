<?php

namespace App\Policies;

class TestimonialPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'testimonials';
    }
}
