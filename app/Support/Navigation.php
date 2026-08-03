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
        Roles::CEO,
        Roles::ICT_MANAGER,
        Roles::CONTENT_MANAGER,
        Roles::MARKETING_OFFICER,
        Roles::HR_DIRECTOR,
        Roles::HR_MANAGER,
        Roles::HR_OFFICER,
    ];

    public static function homeRouteFor(?User $user): string
    {
        if (! $user) {
            return route('login');
        }

        // Registered visitors go to their own account area, never the back office.
        if ($user->hasRole(Roles::CLIENT)) {
            return route('account.dashboard');
        }

        // Staff with no administrative role land in the self-service portal.
        if ($user->hasRole(Roles::EMPLOYEE) && ! $user->hasAnyRole(self::GROUP_ROLES)) {
            return route('portal.dashboard');
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

    /**
     * The address the "Sign in as HR" link fills in.
     *
     * Only ever an email address — no password is exposed, and signing in
     * still goes through the normal form. Returns null when no HR account
     * exists, so the link simply does not render.
     */
    public static function hrSignInAddress(): ?string
    {
        return \Illuminate\Support\Facades\Cache::remember('auth.hr_signin_address', now()->addHour(), function () {
            return User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                    Roles::HR_DIRECTOR, Roles::HR_MANAGER, Roles::HR_OFFICER,
                ]))
                ->where('is_active', true)
                // Prefer a company address over anyone's personal one.
                ->orderByRaw("CASE WHEN email LIKE '%@welldonegroup.com' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->value('email');
        });
    }

    /** True when this user is scoped to a single division. */
    public static function isDivisionScoped(?User $user): bool
    {
        return $user
            && $user->division_id !== null
            && ! $user->hasAnyRole(self::GROUP_ROLES);
    }
}
