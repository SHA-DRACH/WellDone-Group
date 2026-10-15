<?php

namespace App\Policies;

use App\Models\User;
use App\Support\DivisionScope;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Model;

class UserPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'users';
    }

    /**
     * Only a super administrator may modify another super administrator —
     * this prevents an ordinary administrator from escalating by editing the
     * account above them. (The Gate::before hook lets super admins through
     * before this method is reached.)
     */
    public function update(User $user, Model $model): bool
    {
        if (! $user->can('users.update')) {
            return false;
        }

        /** @var User $model */
        return ! $model->hasRole(Roles::SUPER_ADMIN)
            && $this->writableAcrossDivisions($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        if (! $user->can('users.delete')) {
            return false;
        }

        /** @var User $model */
        // No self-deletion, no deleting a super administrator, and nobody
        // outside your own division if you are confined to one.
        return $model->id !== $user->id
            && ! $model->hasRole(Roles::SUPER_ADMIN)
            && $this->writableAcrossDivisions($user, $model);
    }

    /** Assigning roles is a narrower right than editing a profile. */
    public function assignRoles(User $user, ?User $model = null): bool
    {
        if (! $user->can('users.update')) {
            return false;
        }

        if ($model === null) {
            return true;
        }

        return ! $model->hasRole(Roles::SUPER_ADMIN)
            && $this->writableAcrossDivisions($user, $model)
            // Changing your own roles is how a division account would make
            // itself group-wide; that is somebody else's decision.
            && ($model->id !== $user->id || ! DivisionScope::restricts($user));
    }

    /** Anyone signed in may edit their own profile. */
    public function updateProfile(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }
}
