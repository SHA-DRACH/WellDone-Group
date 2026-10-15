<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

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
        Roles::FINANCE_MANAGER,
        Roles::FINANCE_OFFICER,
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

        // Group-level staff see everything — unless their account is attached
        // to a division, in which case that division is all they see.
        if ($user->hasAnyRole(self::GROUP_ROLES) && ! DivisionScope::restricts($user)) {
            return route('admin.dashboard');
        }

        // Division staff go straight to their own division.
        if ($user->division_id && $user->relationLoaded('division') === false) {
            $user->load('division');
        }

        if ($user->division && $user->division->is_active) {
            return route('admin.division.dashboard', $user->division->slug);
        }

        /*
         * Confined to a division, but the division is missing or switched
         * off. The group dashboard is not a safe fallback for them — it is
         * the whole company — so send them somewhere that says so.
         */
        if (DivisionScope::restricts($user)) {
            return route('admin.division.missing');
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
        return Cache::remember('auth.hr_signin_address', now()->addHour(), function () {
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

    /**
     * True when this user is scoped to a single division.
     *
     * One definition, in DivisionScope. Two would eventually disagree, and
     * the disagreement would show up as one screen filtering and another not.
     */
    public static function isDivisionScoped(?User $user): bool
    {
        return DivisionScope::restricts($user);
    }
}
