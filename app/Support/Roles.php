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

    // ------------------------------------------------------------------
    // Human Resources
    // ------------------------------------------------------------------

    public const CEO = 'CEO';

    public const HR_DIRECTOR = 'HR Director';

    public const HR_OFFICER = 'HR Officer';

    public const DEPARTMENT_MANAGER = 'Department Manager';

    /** A member of staff with portal access but no administrative rights. */
    public const EMPLOYEE = 'Employee';

    /** @return array<int, string> */
    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::CEO,
            self::CONTENT_MANAGER,
            self::HR_DIRECTOR,
            self::HR_MANAGER,
            self::HR_OFFICER,
            self::DEPARTMENT_MANAGER,
            self::MARKETING_OFFICER,
            self::SALES_OFFICER,
            self::ICT_MANAGER,
            self::EMPLOYEE,
            self::CLIENT,
        ];
    }

    /** Roles that may sign in to the admin panel. */
    public static function staff(): array
    {
        return array_values(array_diff(self::all(), [self::CLIENT, self::EMPLOYEE]));
    }

    /**
     * Roles whose home is the employee self-service portal rather than the
     * admin panel.
     */
    public static function portalOnly(): array
    {
        return [self::EMPLOYEE];
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

            /*
             * HR Director — the whole HR module, including salary data and
             * the authority to make a hiring decision.
             */
            self::HR_DIRECTOR => array_merge(
                Permissions::forModule('employees'),
                Permissions::forModule('departments'),
                Permissions::forModule('positions'),
                Permissions::forModule('recruitment'),
                Permissions::forModule('announcements'),
                Permissions::forModule('hr_reports'),
                Permissions::forModule('careers'),
                Permissions::forModule('applications'),
                [$p::DASHBOARD_VIEW, $p::CONTACT_VIEW, $p::MEDIA_VIEW, $p::USERS_VIEW],
            ),

            /*
             * HR Manager — runs HR day to day. Can see salary but not change
             * it, and can hire, but department and position structure is the
             * director's to alter.
             */
            self::HR_MANAGER => array_merge(
                Permissions::forModule('careers'),
                Permissions::forModule('applications'),
                Permissions::forModule('recruitment'),
                Permissions::forModule('announcements'),
                Permissions::forModule('hr_reports'),
                [
                    $p::EMPLOYEES_VIEW, $p::EMPLOYEES_CREATE, $p::EMPLOYEES_UPDATE,
                    $p::EMPLOYEES_VIEW_SALARY, $p::EMPLOYEES_VIEW_SENSITIVE,
                    $p::DEPARTMENTS_VIEW, $p::POSITIONS_VIEW,
                    $p::DASHBOARD_VIEW, $p::CONTACT_VIEW, $p::MEDIA_VIEW,
                ],
            ),

            /*
             * HR Officer — administrative support. Maintains records and
             * moves candidates along, but sees no salary or identity
             * documents and cannot make the final hiring decision.
             */
            self::HR_OFFICER => array_merge(
                Permissions::forModule('applications'),
                [
                    $p::EMPLOYEES_VIEW, $p::EMPLOYEES_CREATE, $p::EMPLOYEES_UPDATE,
                    $p::DEPARTMENTS_VIEW, $p::POSITIONS_VIEW,
                    $p::RECRUITMENT_VIEW, $p::RECRUITMENT_UPDATE, $p::RECRUITMENT_SCHEDULE,
                    $p::ANNOUNCEMENTS_VIEW, $p::ANNOUNCEMENTS_CREATE,
                    $p::CAREERS_VIEW, $p::HR_REPORTS_VIEW,
                    $p::DASHBOARD_VIEW, $p::MEDIA_VIEW,
                ],
            ),

            /*
             * Department Manager — sees their own people and the candidates
             * applying to their department. Scoping to their department is
             * enforced on the query, not by permission alone.
             */
            self::DEPARTMENT_MANAGER => [
                $p::DASHBOARD_VIEW,
                $p::EMPLOYEES_VIEW,
                $p::DEPARTMENTS_VIEW,
                $p::POSITIONS_VIEW,
                $p::RECRUITMENT_VIEW,
                $p::RECRUITMENT_UPDATE,
                $p::RECRUITMENT_SCHEDULE,
                $p::APPLICATIONS_VIEW,
                $p::ANNOUNCEMENTS_VIEW,
                $p::HR_REPORTS_VIEW,
            ],

            /*
             * CEO — visibility across the organisation, including salary,
             * plus final sign-off. Deliberately read-mostly: the chief
             * executive approves, they do not maintain records.
             */
            self::CEO => [
                $p::DASHBOARD_VIEW,
                $p::EMPLOYEES_VIEW, $p::EMPLOYEES_VIEW_SALARY, $p::EMPLOYEES_VIEW_SENSITIVE,
                $p::DEPARTMENTS_VIEW, $p::POSITIONS_VIEW,
                $p::RECRUITMENT_VIEW, $p::RECRUITMENT_DECIDE,
                $p::APPLICATIONS_VIEW,
                $p::ANNOUNCEMENTS_VIEW, $p::ANNOUNCEMENTS_CREATE,
                $p::HR_REPORTS_VIEW,
                $p::QUOTES_VIEW, $p::PROJECTS_VIEW, $p::DIVISIONS_VIEW,
                $p::ACTIVITY_VIEW,
            ],

            /*
             * Employee — no administrative permissions at all. Access to the
             * self-service portal comes from having an employee record, not
             * from a permission.
             */
            self::EMPLOYEE => [],

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
