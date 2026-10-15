<?php

namespace App\Policies;

class EmployeeDocumentPolicy extends EmployeeRecordPolicy
{
    protected function module(): string
    {
        return 'employee_documents';
    }
}
