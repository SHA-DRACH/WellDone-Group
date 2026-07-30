<?php

namespace App\Support;

/**
 * The canonical role catalogue.
 *
 * Roles are seeded into the database by RolePermissionSeeder; these constants
 * exist so controllers, policies and Blade templates never hard-code strings.
 */
final class Roles
{
    public const SUPER_ADMIN = 'Super Administrator';

    public const ADMIN = 'Administrator';

    public const CONTENT_MANAGER = 'Content Manager';

    public const HR_MANAGER = 'HR Manager';

    public const MARKETING_OFFICER = 'Marketing Officer';

    public const SALES_OFFICER = 'Sales Officer';

    public const ICT_MANAGER = 'ICT Manager';

    public const CLIENT = 'Client';

    /** @return array<int, string> */
    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::CONTENT_MANAGER,
            self::HR_MANAGER,
            self::MARKETING_OFFICER,
            self::SALES_OFFICER,
            self::ICT_MANAGER,
            self::CLIENT,
        ];
    }

    /** Roles that may sign in to the admin panel. */
    public static function staff(): array
    {
        return array_values(array_diff(self::all(), [self::CLIENT]));
    }

    /**
     * Which permissions each role receives at seed time.
     *
     * Super Administrator is deliberately absent — it is granted everything
     * via a Gate::before hook rather than an explicit permission list, so new
     * permissions never need a re-seed to reach it.
     *
     * @return array<string, array<int, string>>
     */
    public static function permissionMatrix(): array
    {
        $p = Permissions::class;

        return [
            self::ADMIN => array_values(array_diff(
                Permissions::all(),
                // Only a super administrator may edit roles themselves.
                Permissions::forModule('roles')
            )),

            self::CONTENT_MANAGER => array_merge(
                Permissions::forModule('divisions'),
                Permissions::forModule('services'),
                Permissions::forModule('projects'),
                Permissions::forModule('galleries'),
                Permissions::forModule('blogs'),
                Permissions::forModule('testimonials'),
                Permissions::forModule('partners'),
                Permissions::forModule('downloads'),
                Permissions::forModule('sliders'),
                Permissions::forModule('company'),
                Permissions::forModule('seo'),
                Permissions::forModule('media'),
                [$p::CONTACT_VIEW, $p::DASHBOARD_VIEW],
            ),

            self::HR_MANAGER => array_merge(
                Permissions::forModule('careers'),
                Permissions::forModule('applications'),
                [$p::DASHBOARD_VIEW, $p::CONTACT_VIEW, $p::MEDIA_VIEW],
            ),

            self::MARKETING_OFFICER => array_merge(
                Permissions::forModule('blogs'),
                Permissions::forModule('testimonials'),
                Permissions::forModule('partners'),
                Permissions::forModule('newsletters'),
                Permissions::forModule('sliders'),
                Permissions::forModule('seo'),
                Permissions::forModule('media'),
                [$p::DASHBOARD_VIEW, $p::CONTACT_VIEW, $p::CONTACT_REPLY, $p::PROJECTS_VIEW],
            ),

            self::SALES_OFFICER => array_merge(
                Permissions::forModule('quotes'),
                Permissions::forModule('clients'),
                Permissions::forModule('contact'),
                [$p::DASHBOARD_VIEW, $p::PROJECTS_VIEW, $p::SERVICES_VIEW, $p::DIVISIONS_VIEW],
            ),

            self::ICT_MANAGER => array_merge(
                Permissions::forModule('users'),
                Permissions::forModule('settings'),
                Permissions::forModule('activity'),
                Permissions::forModule('media'),
                Permissions::forModule('seo'),
                [$p::DASHBOARD_VIEW],
            ),

            // Clients get no admin permissions; they only reach the client portal.
            self::CLIENT => [],
        ];
    }
}
