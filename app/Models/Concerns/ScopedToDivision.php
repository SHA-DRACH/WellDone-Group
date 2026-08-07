<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\DivisionScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Marks a model as belonging to a business division, and gives it the query
 * scope that confines a division user to their own.
 *
 * Applying the trait is the whole switch. BaseCrudController checks for the
 * scope and applies it automatically, so a new division-owned module is
 * covered by adding this line to its model rather than by remembering to
 * repeat a `where` in a controller — which is the sort of thing that gets
 * forgotten exactly once and leaks a division's data.
 *
 * Deliberately not a global scope. The public website is browsed by the same
 * user object, and a global scope would leave a mining manager viewing the
 * company's own marketing pages with three quarters of them missing.
 *
 * The scope is `withinDivisionOf`, not the shorter `visibleTo`: Announcement
 * already uses that name for audience targeting — which employees a notice is
 * addressed to — and the two answer different questions. One name for both
 * silently shadowed the other and took the HR dashboard down with it.
 */
trait ScopedToDivision
{
    /** The column holding the division. Overridden where it is named differently. */
    public function divisionColumn(): string
    {
        return 'division_id';
    }

    /**
     * Whether records with no division belong to the group (visible to all)
     * or are simply unrouted (visible to nobody until assigned).
     */
    public function unassignedBelongsToGroup(): bool
    {
        return true;
    }

    /** Narrow the query to what this user is allowed to see. */
    public function scopeWithinDivisionOf(Builder $query, ?User $user): Builder
    {
        return DivisionScope::apply(
            $query,
            $user,
            $this->divisionColumn(),
            $this->unassignedBelongsToGroup(),
        );
    }

    /** The division this record belongs to, or null for a group record. */
    public function divisionScopeId(): ?int
    {
        $value = $this->getAttribute($this->divisionColumn());

        return $value === null ? null : (int) $value;
    }

    public function isVisibleTo(?User $user): bool
    {
        return DivisionScope::allows($user, $this->divisionScopeId());
    }

    public function isWritableBy(?User $user): bool
    {
        return DivisionScope::allowsWrite($user, $this->divisionScopeId());
    }
}
