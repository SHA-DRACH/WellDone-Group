<?php

namespace App\Support;

/**
 * The permission catalogue.
 *
 * Every admin capability is named `<module>.<ability>`; policies map model
 * abilities onto these names so authorisation is checked in exactly one way
 * throughout the application.
 */
final class Permissions
{
    public const DASHBOARD_VIEW = 'dashboard.view';

    // Users & access control
    public const USERS_VIEW = 'users.view';
    public const USERS_CREATE = 'users.create';
    public const USERS_UPDATE = 'users.update';
    public const USERS_DELETE = 'users.delete';

    public const ROLES_VIEW = 'roles.view';
    public const ROLES_CREATE = 'roles.create';
    public const ROLES_UPDATE = 'roles.update';
    public const ROLES_DELETE = 'roles.delete';

    // Corporate structure
    public const DIVISIONS_VIEW = 'divisions.view';
    public const DIVISIONS_CREATE = 'divisions.create';
    public const DIVISIONS_UPDATE = 'divisions.update';
    public const DIVISIONS_DELETE = 'divisions.delete';

    public const SERVICES_VIEW = 'services.view';
    public const SERVICES_CREATE = 'services.create';
    public const SERVICES_UPDATE = 'services.update';
    public const SERVICES_DELETE = 'services.delete';

    // Portfolio
    public const PROJECTS_VIEW = 'projects.view';
    public const PROJECTS_CREATE = 'projects.create';
    public const PROJECTS_UPDATE = 'projects.update';
    public const PROJECTS_DELETE = 'projects.delete';

    public const GALLERIES_VIEW = 'galleries.view';
    public const GALLERIES_CREATE = 'galleries.create';
    public const GALLERIES_UPDATE = 'galleries.update';
    public const GALLERIES_DELETE = 'galleries.delete';

    public const CLIENTS_VIEW = 'clients.view';
    public const CLIENTS_CREATE = 'clients.create';
    public const CLIENTS_UPDATE = 'clients.update';
    public const CLIENTS_DELETE = 'clients.delete';

    // Editorial
    public const BLOGS_VIEW = 'blogs.view';
    public const BLOGS_CREATE = 'blogs.create';
    public const BLOGS_UPDATE = 'blogs.update';
    public const BLOGS_DELETE = 'blogs.delete';
    public const BLOGS_PUBLISH = 'blogs.publish';
    public const BLOGS_MODERATE = 'blogs.moderate';

    public const TESTIMONIALS_VIEW = 'testimonials.view';
    public const TESTIMONIALS_CREATE = 'testimonials.create';
    public const TESTIMONIALS_UPDATE = 'testimonials.update';
    public const TESTIMONIALS_DELETE = 'testimonials.delete';

    public const PARTNERS_VIEW = 'partners.view';
    public const PARTNERS_CREATE = 'partners.create';
    public const PARTNERS_UPDATE = 'partners.update';
    public const PARTNERS_DELETE = 'partners.delete';

    public const SLIDERS_VIEW = 'sliders.view';
    public const SLIDERS_CREATE = 'sliders.create';
    public const SLIDERS_UPDATE = 'sliders.update';
    public const SLIDERS_DELETE = 'sliders.delete';

    public const DOWNLOADS_VIEW = 'downloads.view';
    public const DOWNLOADS_CREATE = 'downloads.create';
    public const DOWNLOADS_UPDATE = 'downloads.update';
    public const DOWNLOADS_DELETE = 'downloads.delete';

    // About-us content: team, certificates, milestones, CSR, FAQs
    public const COMPANY_VIEW = 'company.view';
    public const COMPANY_CREATE = 'company.create';
    public const COMPANY_UPDATE = 'company.update';
    public const COMPANY_DELETE = 'company.delete';

    // Recruitment
    public const CAREERS_VIEW = 'careers.view';
    public const CAREERS_CREATE = 'careers.create';
    public const CAREERS_UPDATE = 'careers.update';
    public const CAREERS_DELETE = 'careers.delete';

    public const APPLICATIONS_VIEW = 'applications.view';
    public const APPLICATIONS_UPDATE = 'applications.update';
    public const APPLICATIONS_DELETE = 'applications.delete';
    public const APPLICATIONS_DOWNLOAD = 'applications.download';

    // Leads
    public const QUOTES_VIEW = 'quotes.view';
    public const QUOTES_UPDATE = 'quotes.update';
    public const QUOTES_DELETE = 'quotes.delete';
    public const QUOTES_ASSIGN = 'quotes.assign';
    public const QUOTES_DOWNLOAD = 'quotes.download';

    public const CONTACT_VIEW = 'contact.view';
    public const CONTACT_REPLY = 'contact.reply';
    public const CONTACT_DELETE = 'contact.delete';

    public const NEWSLETTERS_VIEW = 'newsletters.view';
    public const NEWSLETTERS_CREATE = 'newsletters.create';
    public const NEWSLETTERS_DELETE = 'newsletters.delete';
    public const NEWSLETTERS_EXPORT = 'newsletters.export';

    // Platform
    public const MEDIA_VIEW = 'media.view';
    public const MEDIA_CREATE = 'media.create';
    public const MEDIA_DELETE = 'media.delete';

    public const SETTINGS_VIEW = 'settings.view';
    public const SETTINGS_UPDATE = 'settings.update';

    public const SEO_VIEW = 'seo.view';
    public const SEO_UPDATE = 'seo.update';

    public const ACTIVITY_VIEW = 'activity.view';

    // ------------------------------------------------------------------
    // Human Resources
    // ------------------------------------------------------------------

    public const EMPLOYEES_VIEW = 'employees.view';
    public const EMPLOYEES_CREATE = 'employees.create';
    public const EMPLOYEES_UPDATE = 'employees.update';
    public const EMPLOYEES_DELETE = 'employees.delete';

    /**
     * Salary and bank details are a separate right from the employee record.
     * An HR officer maintains staff details every day without needing to see
     * what anyone is paid.
     */
    public const EMPLOYEES_VIEW_SALARY = 'employees.view_salary';
    public const EMPLOYEES_UPDATE_SALARY = 'employees.update_salary';

    /** National ID, passport and date of birth. */
    public const EMPLOYEES_VIEW_SENSITIVE = 'employees.view_sensitive';

    public const DEPARTMENTS_VIEW = 'departments.view';
    public const DEPARTMENTS_CREATE = 'departments.create';
    public const DEPARTMENTS_UPDATE = 'departments.update';
    public const DEPARTMENTS_DELETE = 'departments.delete';

    public const POSITIONS_VIEW = 'positions.view';
    public const POSITIONS_CREATE = 'positions.create';
    public const POSITIONS_UPDATE = 'positions.update';
    public const POSITIONS_DELETE = 'positions.delete';

    public const RECRUITMENT_VIEW = 'recruitment.view';
    public const RECRUITMENT_UPDATE = 'recruitment.update';
    public const RECRUITMENT_SCHEDULE = 'recruitment.schedule';
    public const RECRUITMENT_DECIDE = 'recruitment.decide';
    public const RECRUITMENT_HIRE = 'recruitment.hire';

    public const ANNOUNCEMENTS_VIEW = 'announcements.view';
    public const ANNOUNCEMENTS_CREATE = 'announcements.create';
    public const ANNOUNCEMENTS_UPDATE = 'announcements.update';
    public const ANNOUNCEMENTS_DELETE = 'announcements.delete';

    public const HR_REPORTS_VIEW = 'hr_reports.view';

    /*
     * Leave. Approving is deliberately separate from maintaining requests: an
     * HR officer keys in a paper form on somebody's behalf every day without
     * thereby being able to grant it.
     */
    public const LEAVE_VIEW = 'leave.view';
    public const LEAVE_CREATE = 'leave.create';
    public const LEAVE_UPDATE = 'leave.update';
    public const LEAVE_DELETE = 'leave.delete';
    public const LEAVE_APPROVE = 'leave.approve';

    /** Entitlement rules and per-employee balance adjustments. */
    public const LEAVE_TYPES_VIEW = 'leave_types.view';
    public const LEAVE_TYPES_CREATE = 'leave_types.create';
    public const LEAVE_TYPES_UPDATE = 'leave_types.update';
    public const LEAVE_TYPES_DELETE = 'leave_types.delete';
    public const LEAVE_BALANCES_ADJUST = 'leave_types.adjust_balances';

    /** Courses, certifications and their expiry dates. */
    public const TRAINING_VIEW = 'training.view';
    public const TRAINING_CREATE = 'training.create';
    public const TRAINING_UPDATE = 'training.update';
    public const TRAINING_DELETE = 'training.delete';

    /**
     * Contracts, ID copies, permits. Kept on the private disk and streamed
     * through a controller, so the permission is the only way in.
     */
    public const EMPLOYEE_DOCUMENTS_VIEW = 'employee_documents.view';
    public const EMPLOYEE_DOCUMENTS_CREATE = 'employee_documents.create';
    public const EMPLOYEE_DOCUMENTS_UPDATE = 'employee_documents.update';
    public const EMPLOYEE_DOCUMENTS_DELETE = 'employee_documents.delete';

    // ------------------------------------------------------------------
    // Division operations
    // ------------------------------------------------------------------

    /**
     * The day-to-day log each division keeps on its dashboard — production
     * for the quarry, shipments for logistics, and so on. Confined to the
     * user's own division by DivisionScope, like everything else they see.
     */
    public const DIVISION_RECORDS_VIEW = 'division_records.view';
    public const DIVISION_RECORDS_CREATE = 'division_records.create';
    public const DIVISION_RECORDS_UPDATE = 'division_records.update';
    public const DIVISION_RECORDS_DELETE = 'division_records.delete';
    public const DIVISION_RECORDS_EXPORT = 'division_records.export';

    // ------------------------------------------------------------------
    // Finance
    // ------------------------------------------------------------------

    /*
     * Preparing a payroll and approving it are separate rights, and the
     * policy also refuses to let the person who prepared a run approve it.
     * Two people have to agree before anybody is paid.
     */
    public const PAYROLL_VIEW = 'payroll.view';
    public const PAYROLL_PROCESS = 'payroll.process';
    public const PAYROLL_APPROVE = 'payroll.approve';

    /** Submitting an expense and approving it are, likewise, two rights. */
    public const EXPENSES_VIEW = 'expenses.view';
    public const EXPENSES_CREATE = 'expenses.create';
    public const EXPENSES_UPDATE = 'expenses.update';
    public const EXPENSES_DELETE = 'expenses.delete';
    public const EXPENSES_APPROVE = 'expenses.approve';

    public const INVOICES_VIEW = 'invoices.view';
    public const INVOICES_CREATE = 'invoices.create';
    public const INVOICES_UPDATE = 'invoices.update';
    public const INVOICES_DELETE = 'invoices.delete';
    public const INVOICES_PAYMENTS = 'invoices.payments';

    public const BUDGETS_VIEW = 'budgets.view';
    public const BUDGETS_CREATE = 'budgets.create';
    public const BUDGETS_UPDATE = 'budgets.update';
    public const BUDGETS_DELETE = 'budgets.delete';

    /** The finance dashboard, profit & loss and budget-versus-actual. */
    public const FINANCE_REPORTS_VIEW = 'finance_reports.view';

    /** Tax bands, social security rates, invoice terms. */
    public const FINANCE_SETTINGS = 'finance_reports.settings';

    /**
     * Every permission, grouped by module. The keys drive the grouped
     * checkbox layout on the role edit screen.
     *
     * @return array<string, array<int, string>>
     */
    public static function grouped(): array
    {
        return [
            'dashboard' => [self::DASHBOARD_VIEW],
            'users' => [self::USERS_VIEW, self::USERS_CREATE, self::USERS_UPDATE, self::USERS_DELETE],
            'roles' => [self::ROLES_VIEW, self::ROLES_CREATE, self::ROLES_UPDATE, self::ROLES_DELETE],
            'divisions' => [self::DIVISIONS_VIEW, self::DIVISIONS_CREATE, self::DIVISIONS_UPDATE, self::DIVISIONS_DELETE],
            'services' => [self::SERVICES_VIEW, self::SERVICES_CREATE, self::SERVICES_UPDATE, self::SERVICES_DELETE],
            'projects' => [self::PROJECTS_VIEW, self::PROJECTS_CREATE, self::PROJECTS_UPDATE, self::PROJECTS_DELETE],
            'galleries' => [self::GALLERIES_VIEW, self::GALLERIES_CREATE, self::GALLERIES_UPDATE, self::GALLERIES_DELETE],
            'clients' => [self::CLIENTS_VIEW, self::CLIENTS_CREATE, self::CLIENTS_UPDATE, self::CLIENTS_DELETE],
            'blogs' => [self::BLOGS_VIEW, self::BLOGS_CREATE, self::BLOGS_UPDATE, self::BLOGS_DELETE, self::BLOGS_PUBLISH, self::BLOGS_MODERATE],
            'testimonials' => [self::TESTIMONIALS_VIEW, self::TESTIMONIALS_CREATE, self::TESTIMONIALS_UPDATE, self::TESTIMONIALS_DELETE],
            'partners' => [self::PARTNERS_VIEW, self::PARTNERS_CREATE, self::PARTNERS_UPDATE, self::PARTNERS_DELETE],
            'sliders' => [self::SLIDERS_VIEW, self::SLIDERS_CREATE, self::SLIDERS_UPDATE, self::SLIDERS_DELETE],
            'downloads' => [self::DOWNLOADS_VIEW, self::DOWNLOADS_CREATE, self::DOWNLOADS_UPDATE, self::DOWNLOADS_DELETE],
            'company' => [self::COMPANY_VIEW, self::COMPANY_CREATE, self::COMPANY_UPDATE, self::COMPANY_DELETE],
            'careers' => [self::CAREERS_VIEW, self::CAREERS_CREATE, self::CAREERS_UPDATE, self::CAREERS_DELETE],
            'applications' => [self::APPLICATIONS_VIEW, self::APPLICATIONS_UPDATE, self::APPLICATIONS_DELETE, self::APPLICATIONS_DOWNLOAD],
            'quotes' => [self::QUOTES_VIEW, self::QUOTES_UPDATE, self::QUOTES_DELETE, self::QUOTES_ASSIGN, self::QUOTES_DOWNLOAD],
            'contact' => [self::CONTACT_VIEW, self::CONTACT_REPLY, self::CONTACT_DELETE],
            'newsletters' => [self::NEWSLETTERS_VIEW, self::NEWSLETTERS_CREATE, self::NEWSLETTERS_DELETE, self::NEWSLETTERS_EXPORT],
            'media' => [self::MEDIA_VIEW, self::MEDIA_CREATE, self::MEDIA_DELETE],
            'settings' => [self::SETTINGS_VIEW, self::SETTINGS_UPDATE],
            'seo' => [self::SEO_VIEW, self::SEO_UPDATE],
            'activity' => [self::ACTIVITY_VIEW],

            // ---------------------------------------------- Human Resources
            'employees' => [
                self::EMPLOYEES_VIEW, self::EMPLOYEES_CREATE, self::EMPLOYEES_UPDATE, self::EMPLOYEES_DELETE,
                self::EMPLOYEES_VIEW_SALARY, self::EMPLOYEES_UPDATE_SALARY, self::EMPLOYEES_VIEW_SENSITIVE,
            ],
            'departments' => [
                self::DEPARTMENTS_VIEW, self::DEPARTMENTS_CREATE,
                self::DEPARTMENTS_UPDATE, self::DEPARTMENTS_DELETE,
            ],
            'positions' => [
                self::POSITIONS_VIEW, self::POSITIONS_CREATE,
                self::POSITIONS_UPDATE, self::POSITIONS_DELETE,
            ],
            'recruitment' => [
                self::RECRUITMENT_VIEW, self::RECRUITMENT_UPDATE, self::RECRUITMENT_SCHEDULE,
                self::RECRUITMENT_DECIDE, self::RECRUITMENT_HIRE,
            ],
            'announcements' => [
                self::ANNOUNCEMENTS_VIEW, self::ANNOUNCEMENTS_CREATE,
                self::ANNOUNCEMENTS_UPDATE, self::ANNOUNCEMENTS_DELETE,
            ],
            'hr_reports' => [self::HR_REPORTS_VIEW],
            'leave' => [
                self::LEAVE_VIEW, self::LEAVE_CREATE, self::LEAVE_UPDATE,
                self::LEAVE_DELETE, self::LEAVE_APPROVE,
            ],
            'leave_types' => [
                self::LEAVE_TYPES_VIEW, self::LEAVE_TYPES_CREATE, self::LEAVE_TYPES_UPDATE,
                self::LEAVE_TYPES_DELETE, self::LEAVE_BALANCES_ADJUST,
            ],
            'training' => [
                self::TRAINING_VIEW, self::TRAINING_CREATE,
                self::TRAINING_UPDATE, self::TRAINING_DELETE,
            ],
            'employee_documents' => [
                self::EMPLOYEE_DOCUMENTS_VIEW, self::EMPLOYEE_DOCUMENTS_CREATE,
                self::EMPLOYEE_DOCUMENTS_UPDATE, self::EMPLOYEE_DOCUMENTS_DELETE,
            ],

            // ---------------------------------------------- Division operations
            'division_records' => [
                self::DIVISION_RECORDS_VIEW, self::DIVISION_RECORDS_CREATE, self::DIVISION_RECORDS_UPDATE,
                self::DIVISION_RECORDS_DELETE, self::DIVISION_RECORDS_EXPORT,
            ],

            // ---------------------------------------------- Finance
            'payroll' => [self::PAYROLL_VIEW, self::PAYROLL_PROCESS, self::PAYROLL_APPROVE],
            'expenses' => [
                self::EXPENSES_VIEW, self::EXPENSES_CREATE, self::EXPENSES_UPDATE,
                self::EXPENSES_DELETE, self::EXPENSES_APPROVE,
            ],
            'invoices' => [
                self::INVOICES_VIEW, self::INVOICES_CREATE, self::INVOICES_UPDATE,
                self::INVOICES_DELETE, self::INVOICES_PAYMENTS,
            ],
            'budgets' => [self::BUDGETS_VIEW, self::BUDGETS_CREATE, self::BUDGETS_UPDATE, self::BUDGETS_DELETE],
            'finance_reports' => [self::FINANCE_REPORTS_VIEW, self::FINANCE_SETTINGS],
        ];
    }

    /** @return array<int, string> */
    public static function all(): array
    {
        return array_merge(...array_values(self::grouped()));
    }

    /** @return array<int, string> */
    public static function forModule(string $module): array
    {
        return self::grouped()[$module] ?? [];
    }

    /** Human label for a permission name, e.g. "projects.create" → "Create". */
    public static function label(string $permission): string
    {
        $ability = str($permission)->after('.')->replace('_', ' ')->title()->toString();

        return $ability;
    }

    /** Human label for a module key, e.g. "blogs" → "News & Blog". */
    public static function moduleLabel(string $module): string
    {
        return match ($module) {
            'blogs' => 'News & Blog',
            'seo' => 'SEO Metadata',
            'company' => 'Company Profile',
            'applications' => 'Job Applications',
            'quotes' => 'Quote Requests',
            'contact' => 'Contact Messages',
            'activity' => 'Activity Log',
            'media' => 'Media Library',
            'employees' => 'Employee Records',
            'recruitment' => 'Recruitment',
            'hr_reports' => 'HR Reports',
            'leave' => 'Leave Requests',
            'leave_types' => 'Leave Entitlement',
            'announcements' => 'Internal Announcements',
            'training' => 'Training & Certification',
            'employee_documents' => 'Employee Documents',
            'division_records' => 'Division Operations Log',
            'finance_reports' => 'Finance Reports',
            default => str($module)->replace('_', ' ')->title()->toString(),
        };
    }
}
