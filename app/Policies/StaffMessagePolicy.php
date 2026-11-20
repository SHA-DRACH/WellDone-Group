<?php

namespace App\Policies;

use App\Models\StaffMessage;
use App\Models\User;
use App\Support\Permissions;

/**
 * A staff message is private to its sender and recipient. Starting a new
 * message needs the permission; replying to one you received does not.
 */
class StaffMessagePolicy
{
    public function view(User $user, StaffMessage $message): bool
    {
        return $message->involves($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::STAFF_MESSAGES_SEND);
    }

    public function reply(User $user, StaffMessage $message): bool
    {
        return $message->recipient_id === $user->id && $message->sender_id !== null;
    }
}
