<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Decides whose records a signed-in user is allowed to see.
 *
 * WellDone runs four business divisions, and a division head answers for one
 * of them. The rule this class encodes is that such a user sees their own
 * division and nothing else — not another division's quotation requests, not
 * its projects, not its staff.
 *
 * Two design decisions are worth stating, because both are load-bearing.
 *
 * First, the restriction is applied to the query, never to the view. Hiding
 * a row in Blade leaves it reachable by typing its URL, guessing an id, or
 * paging past the filter. Everything here returns a narrowed query instead.
 *
 * Second, it fails closed. A user who is meant to be confined to a division
 * but has none recorded is given an impossible predicate rather than an
 * absent one, so a half-finished account shows nothing rather than
 * everything. Getting that backwards is how a scoping bug turns into a leak.
 */
final class DivisionScope
{
    /**
     * Roles that are confined to one division regardless of anything else.
     *
     * Membership here is not what triggers the restriction — any user outside
     * the group-level roles with a division recorded is scoped. It exists so
     * that a division role with no division set is still refused, instead of
     * quietly falling through to seeing the whole group.
     */
    public const CONFINED_ROLES = [
        Roles::DIVISION_MANAGER,
        Roles::DIVISION_OFFICER,
    ];

    /** True when this user may only see one division's records. */
    public static function restricts(?User $user): bool
    {
        if (! $user) {
            // The public site is not scoped; it shows what is published.
            return false;
        }

        if ($user->hasAnyRole(Navigation::GROUP_ROLES)) {
            return false;
        }

        return $user->division_id !== null
            || $user->hasAnyRole(self::CONFINED_ROLES);
    }

    /**
     * The division this user is confined to.
     *
     * Returns null when they are not confined at all. Returns 0 — an id no
     * row can hold — when they are confined but no division is recorded, so
     * the resulting query matches nothing.
     */
    public static function idFor(?User $user): ?int
    {
        if (! self::restricts($user)) {
            return null;
        }

        return $user->division_id ?? 0;
    }

    /**
     * Narrow a query to the user's division.
     *
     * Records belonging to no division are included by default. Those are the
     * group's own — a company-wide announcement, a corporate brochure, a
     * service offered by the whole company — and they are not another
     * division's property to be shielded from. Hiding them would leave a
     * division head unable to read company policy.
     *
     * Pass `$includeUnassigned: false` where an absent division means "not
     * routed yet" rather than "belongs to the group", which is how the
     * incoming-lead tables read it: an unrouted enquiry is nobody's until it
     * is assigned, and showing it to all four divisions is noise, not access.
     *
     * `$column` is qualified against the model's table so this stays correct
     * inside a join, where a bare `division_id` would be ambiguous.
     */
    public static function apply(
        Builder $query,
        ?User $user,
        string $column = 'division_id',
        bool $includeUnassigned = true,
    ): Builder {
        $id = self::idFor($user);

        if ($id === null) {
            return $query;
        }

        $qualified = $query->getModel()->qualifyColumn($column);

        if (! $includeUnassigned) {
            return $query->where($qualified, $id);
        }

        return $query->where(fn (Builder $inner) => $inner
            ->where($qualified, $id)
            ->orWhereNull($qualified));
    }

    /**
     * Whether a record carrying this division id may be opened by the user.
     */
    public static function allows(?User $user, ?int $divisionId): bool
    {
        $scope = self::idFor($user);

        return $scope === null || $divisionId === null || $divisionId === $scope;
    }

    /**
     * Whether the user may change a record carrying this division id.
     *
     * Stricter than reading, and deliberately so: a division head can see the
     * group's own records but must not edit them. A company announcement
     * edited by one division would change what the other three are told.
     */
    public static function allowsWrite(?User $user, ?int $divisionId): bool
    {
        $scope = self::idFor($user);

        return $scope === null || $divisionId === $scope;
    }

    /** Refuse the request outright when the record is out of scope. */
    public static function assertVisible(?User $user, ?int $divisionId, string $noun = 'record'): void
    {
        abort_unless(self::allows($user, $divisionId), 403,
            'This '.$noun.' belongs to another division.');
    }

    public static function assertWritable(?User $user, ?int $divisionId, string $noun = 'record'): void
    {
        abort_unless(self::allowsWrite($user, $divisionId), 403,
            $divisionId === null
                ? 'This '.$noun.' belongs to the group and can only be changed centrally.'
                : 'This '.$noun.' belongs to another division.');
    }
}
