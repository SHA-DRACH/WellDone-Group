<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * SEO metadata has no create/delete of its own — records are attached to the
 * page or model they describe, so editing is the only meaningful ability.
 */
class SeoPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'seo';
    }

    public function create(User $user): bool
    {
        return $user->can('seo.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can('seo.update');
    }
}
