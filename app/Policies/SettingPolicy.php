<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Settings rows are defined by the application, not created ad hoc, so only
 * viewing and updating are permitted.
 */
class SettingPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'settings';
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }
}
