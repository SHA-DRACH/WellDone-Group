<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for the straightforward CRUD modules.
 *
 * Each concrete policy declares the permission prefix it guards (e.g.
 * `projects`), and the standard abilities map onto `<prefix>.view`,
 * `.create`, `.update` and `.delete`. Modules with extra rules override the
 * individual methods.
 *
 * A super administrator short-circuits every check via the Gate::before hook
 * registered in AuthServiceProvider, so it is never consulted here.
 */
abstract class ModulePolicy
{
    use HandlesAuthorization;

    /** Permission prefix this policy guards. */
    abstract protected function module(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->permission('view'));
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->permission('view'))
            && $this->readableAcrossDivisions($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can($this->permission('create'));
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permission('update'))
            && $this->writableAcrossDivisions($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->permission('delete'))
            && $this->writableAcrossDivisions($user, $model);
    }

    public function restore(User $user, Model $model): bool
    {
        return $user->can($this->permission('delete'))
            && $this->writableAcrossDivisions($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        // Permanent deletion is reserved for super administrators, which the
        // Gate::before hook grants; everyone else is refused here.
        return false;
    }

    protected function permission(string $ability): string
    {
        return $this->module().'.'.$ability;
    }

    /**
     * A permission says what a role may do; the division says to whose
     * records. Both have to hold, and this is where they meet — so every
     * caller of `authorize()`, not only the shared CRUD controller, gets the
     * confinement.
     *
     * Models with no division are unaffected: the trait supplies these
     * methods, and a model without it answers neither.
     */
    protected function readableAcrossDivisions(User $user, Model $model): bool
    {
        return ! method_exists($model, 'isVisibleTo') || $model->isVisibleTo($user);
    }

    protected function writableAcrossDivisions(User $user, Model $model): bool
    {
        return ! method_exists($model, 'isWritableBy') || $model->isWritableBy($user);
    }
}
