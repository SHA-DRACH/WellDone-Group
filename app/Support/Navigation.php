<?php

namespace App\Support;

use App\Models\User;

/**
 * Decides where a user lands after signing in.
 *
 * There is a single sign-in form for everyone. Where you go next depends on
 * what you are: group-level staff get the full control panel, while staff
 * attached to a business division land on that division's own dashboard.
 */
final class Navigation
{
    /** Roles that oversee the whole group rather than one division. */
    public const GROUP_ROLES = [
        Roles::SUPER_ADMIN,
        Roles::ADMIN,
        Roles::ICT_MANAGER,
        Roles::CONTENT_MANAGER,
        Roles::MARKETING_OFFICER,
        Roles::HR_MANAGER,
    ];

    public static function homeRouteFor(?User $user): string
    {
        if (! $user) {
            return route('login');
        }

        // Clients never see the back office.
        if ($user->hasRole(Roles::CLIENT)) {
            return route('site.home');
        }

        // Group-level staff see everything.
        if ($user->hasAnyRole(self::GROUP_ROLES)) {
            return route('admin.dashboard');
        }

        // Division staff go straight to their own division.
        if ($user->division_id && $user->relationLoaded('division') === false) {
            $user->load('division');
        }

        if ($user->division && $user->division->is_active) {
            return route('admin.division.dashboard', $user->division->slug);
        }

        return route('admin.dashboard');
    }

    /** True when this user is scoped to a single division. */
    public static function isDivisionScoped(?User $user): bool
    {
        return $user
            && $user->division_id !== null
            && ! $user->hasAnyRole(self::GROUP_ROLES);
    }
}
