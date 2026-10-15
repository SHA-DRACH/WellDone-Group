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
    // Business divisions
    // ------------------------------------------------------------------

    /**
     * The head of one business division — Mining & Quarry, Procurement &
     * Logistics, Medical Equipment, ICT Services.
     *
     * Runs their division and sees only their division. The confinement comes
     * from App\Support\DivisionScope and is applied to the query, not to the
     * navigation, so it holds when a URL is typed by hand.
     */
    public const DIVISION_MANAGER = 'Division Manager';

    /** Works inside one division under its head; may act, but not delete. */
    public const DIVISION_OFFICER = 'Division Officer';

    // ------------------------------------------------------------------
    // Human Resources
    // ------------------------------------------------------------------

    public const CEO = 'CEO';

    public const HR_DIRECTOR = 'HR Director';

    public const HR_OFFICER = 'HR Officer';

    public const DEPARTMENT_MANAGER = 'Department Manager';

    /** A member of staff with portal access but no administrative rights. */
    public const EMPLOYEE = 'Employee';

    // ------------------------------------------------------------------
    // Finance
    // ------------------------------------------------------------------

    /** Runs the books: payroll, invoicing, expenses and budgets. */
    public const FINANCE_MANAGER = 'Finance Manager';

    /** Prepares and records; approval stays with the manager and the CEO. */
    public const FINANCE_OFFICER = 'Finance Officer';

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
            self::FINANCE_MANAGER,
            self::FINANCE_OFFICER,
            self::MARKETING_OFFICER,
            self::SALES_OFFICER,
            self::ICT_MANAGER,
            self::DIVISION_MANAGER,
            self::DIVISION_OFFICER,
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
                Permissions::forModule('leave'),
                Permissions::forModule('leave_types'),
                Permissions::forModule('training'),
                Permissions::forModule('employee_documents'),
                [$p::DASHBOARD_VIEW, $p::CONTACT_VIEW, $p::MEDIA_VIEW, $p::USERS_VIEW, $p::PAYROLL_VIEW],
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
                Permissions::forModule('training'),
                Permissions::forModule('employee_documents'),
                [
                    $p::EMPLOYEES_VIEW, $p::EMPLOYEES_CREATE, $p::EMPLOYEES_UPDATE,
                    $p::EMPLOYEES_VIEW_SALARY, $p::EMPLOYEES_VIEW_SENSITIVE,
                    $p::DEPARTMENTS_VIEW, $p::POSITIONS_VIEW,
                    $p::DASHBOARD_VIEW, $p::CONTACT_VIEW, $p::MEDIA_VIEW,
                    $p::LEAVE_VIEW, $p::LEAVE_CREATE, $p::LEAVE_UPDATE, $p::LEAVE_APPROVE,
                    $p::LEAVE_TYPES_VIEW, $p::LEAVE_BALANCES_ADJUST,
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
                    // Keys in the paper forms; granting them is not theirs.
                    $p::LEAVE_VIEW, $p::LEAVE_CREATE, $p::LEAVE_UPDATE,
                    $p::LEAVE_TYPES_VIEW,
                    $p::TRAINING_VIEW, $p::TRAINING_CREATE, $p::TRAINING_UPDATE,
                    $p::EMPLOYEE_DOCUMENTS_VIEW, $p::EMPLOYEE_DOCUMENTS_CREATE, $p::EMPLOYEE_DOCUMENTS_UPDATE,
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
                // Approves their own department's leave; the policy scopes it.
                $p::LEAVE_VIEW,
                $p::LEAVE_APPROVE,
                $p::TRAINING_VIEW,
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
                $p::LEAVE_VIEW, $p::LEAVE_TYPES_VIEW,
                $p::TRAINING_VIEW, $p::EMPLOYEE_DOCUMENTS_VIEW,
                $p::DIVISION_RECORDS_VIEW, $p::DIVISION_RECORDS_EXPORT,
                // Final sign-off on money, without keeping the books.
                $p::PAYROLL_VIEW, $p::PAYROLL_APPROVE,
                $p::EXPENSES_VIEW, $p::EXPENSES_APPROVE,
                $p::INVOICES_VIEW, $p::BUDGETS_VIEW, $p::FINANCE_REPORTS_VIEW,
            ],

            /*
             * Finance Manager — the whole finance module, plus read access to
             * what invoices and payroll are built from: clients, quotations,
             * projects and employee pay.
             */
            self::FINANCE_MANAGER => array_merge(
                Permissions::forModule('payroll'),
                Permissions::forModule('expenses'),
                Permissions::forModule('invoices'),
                Permissions::forModule('budgets'),
                Permissions::forModule('finance_reports'),
                [
                    $p::DASHBOARD_VIEW, $p::MEDIA_VIEW,
                    $p::CLIENTS_VIEW, $p::QUOTES_VIEW, $p::PROJECTS_VIEW, $p::DIVISIONS_VIEW,
                    $p::EMPLOYEES_VIEW, $p::EMPLOYEES_VIEW_SALARY,
                    $p::DIVISION_RECORDS_VIEW, $p::DIVISION_RECORDS_EXPORT,
                ],
            ),

            /*
             * Finance Officer — prepares payroll, raises invoices, records
             * payments and expenses. Approving any of it is not theirs.
             */
            self::FINANCE_OFFICER => [
                $p::DASHBOARD_VIEW, $p::MEDIA_VIEW,
                $p::PAYROLL_VIEW, $p::PAYROLL_PROCESS,
                $p::EXPENSES_VIEW, $p::EXPENSES_CREATE, $p::EXPENSES_UPDATE,
                $p::INVOICES_VIEW, $p::INVOICES_CREATE, $p::INVOICES_UPDATE, $p::INVOICES_PAYMENTS,
                $p::BUDGETS_VIEW, $p::FINANCE_REPORTS_VIEW,
                $p::CLIENTS_VIEW, $p::QUOTES_VIEW, $p::PROJECTS_VIEW,
                $p::EMPLOYEES_VIEW, $p::EMPLOYEES_VIEW_SALARY,
            ],

            /*
             * Employee — no administrative permissions at all. Access to the
             * self-service portal comes from having an employee record, not
             * from a permission.
             */
            self::EMPLOYEE => [],

            /*
             * Division Manager — the head of one business division.
             *
             * The permissions read as though they were group-wide; they are
             * not. Every one of them is applied through DivisionScope, which
             * narrows the query to the division recorded on the account. The
             * separation matters: a permission answers "may this role do this
             * at all", and the scope answers "to whose records". Trying to
             * express confinement as a permission would need one role per
             * division and a re-seed every time a division is added.
             */
            self::DIVISION_MANAGER => array_merge(
                Permissions::forModule('quotes'),
                Permissions::forModule('projects'),
                Permissions::forModule('services'),
                Permissions::forModule('galleries'),
                Permissions::forModule('careers'),
                Permissions::forModule('applications'),
                Permissions::forModule('clients'),
                Permissions::forModule('downloads'),
                Permissions::forModule('division_records'),
                [
                    $p::DASHBOARD_VIEW,
                    $p::DIVISIONS_VIEW,
                    // Their own division's spending and billing, scoped by
                    // DivisionScope. They submit expenses; finance approves.
                    $p::EXPENSES_VIEW, $p::EXPENSES_CREATE,
                    $p::INVOICES_VIEW, $p::BUDGETS_VIEW,
                    $p::CONTACT_VIEW, $p::CONTACT_REPLY,
                    $p::TESTIMONIALS_VIEW, $p::TESTIMONIALS_CREATE, $p::TESTIMONIALS_UPDATE,
                    // Team, FAQs and certificates are group content; a
                    // division head reads them but does not rewrite them.
                    $p::COMPANY_VIEW,
                    $p::MEDIA_VIEW, $p::MEDIA_CREATE,
                    // Their own people, without pay or identity documents —
                    // those stay with HR and the CEO.
                    $p::EMPLOYEES_VIEW,
                    $p::DEPARTMENTS_VIEW, $p::POSITIONS_VIEW,
                    $p::ANNOUNCEMENTS_VIEW,
                    $p::LEAVE_VIEW, $p::LEAVE_APPROVE,
                ],
            ),

            /*
             * Division Officer — day-to-day work inside one division. Same
             * confinement, without the authority to delete anything or to
             * approve leave.
             */
            self::DIVISION_OFFICER => [
                $p::DASHBOARD_VIEW,
                $p::DIVISIONS_VIEW,
                $p::QUOTES_VIEW, $p::QUOTES_UPDATE, $p::QUOTES_ASSIGN,
                $p::PROJECTS_VIEW, $p::PROJECTS_UPDATE,
                $p::SERVICES_VIEW,
                $p::GALLERIES_VIEW, $p::GALLERIES_CREATE, $p::GALLERIES_UPDATE,
                $p::CAREERS_VIEW,
                $p::APPLICATIONS_VIEW, $p::APPLICATIONS_UPDATE,
                $p::CLIENTS_VIEW,
                $p::CONTACT_VIEW, $p::CONTACT_REPLY,
                $p::MEDIA_VIEW, $p::MEDIA_CREATE,
                $p::ANNOUNCEMENTS_VIEW,
                $p::DIVISION_RECORDS_VIEW, $p::DIVISION_RECORDS_CREATE,
                $p::DIVISION_RECORDS_UPDATE, $p::DIVISION_RECORDS_EXPORT,
                $p::EXPENSES_VIEW, $p::EXPENSES_CREATE,
            ],

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
