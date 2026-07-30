<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NewsletterPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'newsletters';
    }

    /** Subscriber records are not edited — only added, removed or exported. */
    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function export(User $user): bool
    {
        return $user->can('newsletters.export');
    }
}
