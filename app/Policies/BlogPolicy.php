<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Article permissions distinguish editing from publishing: a contributor may
 * hold `blogs.update` and still be unable to make a post live.
 */
class BlogPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'blogs';
    }

    /**
     * A published article can only be edited by someone who could also
     * publish it — otherwise a contributor could silently alter live content.
     */
    public function update(User $user, Model $model): bool
    {
        if (! $user->can('blogs.update')) {
            return false;
        }

        /** @var Blog $model */
        if ($model->status === 'published' && ! $user->can('blogs.publish')) {
            return $model->user_id === $user->id;
        }

        return true;
    }

    public function publish(User $user, ?Blog $blog = null): bool
    {
        return $user->can('blogs.publish');
    }

    /** Approving, rejecting and deleting reader comments. */
    public function moderate(User $user): bool
    {
        return $user->can('blogs.moderate');
    }
}
