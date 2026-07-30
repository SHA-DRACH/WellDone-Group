<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ContactMessagePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'contact';
    }

    public function create(User $user): bool
    {
        return false;
    }

    /** Marking as read/archived/spam counts as replying-level access. */
    public function update(User $user, Model $model): bool
    {
        return $user->can('contact.reply');
    }

    public function reply(User $user, ?ContactMessage $message = null): bool
    {
        return $user->can('contact.reply');
    }
}
