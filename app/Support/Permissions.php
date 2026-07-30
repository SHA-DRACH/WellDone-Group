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
            default => str($module)->replace('_', ' ')->title()->toString(),
        };
    }
}
