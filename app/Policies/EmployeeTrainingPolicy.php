<?php

namespace App\Policies;

class EmployeeTrainingPolicy extends EmployeeRecordPolicy
{
    protected function module(): string
    {
        return 'training';
    }
}
