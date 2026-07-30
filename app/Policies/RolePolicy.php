<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

/**
 * Roles are guarded tightly: only a super administrator may reach this module
 * at all (the permission is granted to no other role), and the built-in roles
 * cannot be renamed or deleted even by one.
 */
class RolePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'roles';
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $user->can('roles.delete')) {
            return false;
        }

        /** @var Role $model */
        // Never delete a role the application itself depends on, and never
        // delete a role that still has members.
        return ! in_array($model->name, Roles::all(), true)
            && $model->users()->count() === 0;
    }

    /** The super administrator role's grants are fixed by design. */
    public function update(User $user, Model $model): bool
    {
        if (! $user->can('roles.update')) {
            return false;
        }

        /** @var Role $model */
        return $model->name !== Roles::SUPER_ADMIN;
    }
}
