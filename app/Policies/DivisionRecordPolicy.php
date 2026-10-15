<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Permissions;

/**
 * The operations log. Standard module rules — and through ModulePolicy, the
 * division confinement — plus a separate right to export.
 */
class DivisionRecordPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'division_records';
    }

    public function export(User $user): bool
    {
        return $user->can(Permissions::DIVISION_RECORDS_EXPORT);
    }
}
