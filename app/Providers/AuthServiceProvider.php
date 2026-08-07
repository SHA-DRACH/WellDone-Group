<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Career;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\CsrInitiative;
use App\Models\Division;
use App\Models\Download;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\JobApplication;
use App\Models\Milestone;
use App\Models\Newsletter;
use App\Models\Partner;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Seo;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Policies\BlogCategoryPolicy;
use App\Policies\BlogPolicy;
use App\Policies\CareerPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\ClientPolicy;
use App\Policies\ContactMessagePolicy;
use App\Policies\CsrInitiativePolicy;
use App\Policies\DivisionPolicy;
use App\Policies\DownloadPolicy;
use App\Policies\FaqPolicy;
use App\Policies\GalleryPolicy;
use App\Policies\JobApplicationPolicy;
use App\Policies\MilestonePolicy;
use App\Policies\NewsletterPolicy;
use App\Policies\PartnerPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\QuoteRequestPolicy;
use App\Policies\RolePolicy;
use App\Policies\SeoPolicy;
use App\Policies\ServicePolicy;
use App\Policies\SettingPolicy;
use App\Policies\SliderPolicy;
use App\Policies\TeamMemberPolicy;
use App\Policies\TestimonialPolicy;
use App\Policies\UserPolicy;
use App\Support\Roles;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Division::class => DivisionPolicy::class,
        Service::class => ServicePolicy::class,
        Client::class => ClientPolicy::class,
        Project::class => ProjectPolicy::class,
        Gallery::class => GalleryPolicy::class,
        Blog::class => BlogPolicy::class,
        BlogCategory::class => BlogCategoryPolicy::class,
        Testimonial::class => TestimonialPolicy::class,
        Partner::class => PartnerPolicy::class,
        Slider::class => SliderPolicy::class,
        Download::class => DownloadPolicy::class,
        Career::class => CareerPolicy::class,
        JobApplication::class => JobApplicationPolicy::class,
        QuoteRequest::class => QuoteRequestPolicy::class,
        ContactMessage::class => ContactMessagePolicy::class,
        Newsletter::class => NewsletterPolicy::class,
        TeamMember::class => TeamMemberPolicy::class,
        Certificate::class => CertificatePolicy::class,
        Milestone::class => MilestonePolicy::class,
        CsrInitiative::class => CsrInitiativePolicy::class,
        Faq::class => FaqPolicy::class,
        Setting::class => SettingPolicy::class,
        Seo::class => SeoPolicy::class,

        // Human Resources
        \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
        \App\Models\Department::class => \App\Policies\DepartmentPolicy::class,
        \App\Models\Position::class => \App\Policies\PositionPolicy::class,
        \App\Models\Announcement::class => \App\Policies\AnnouncementPolicy::class,
        \App\Models\LeaveRequest::class => \App\Policies\LeaveRequestPolicy::class,
        \App\Models\LeaveType::class => \App\Policies\LeaveTypePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        /*
         * Two global rules, both resolved before any policy runs:
         *
         *  1. A deactivated account keeps its roles but is authorised for
         *     nothing, so disabling a user takes effect immediately without
         *     having to strip their roles. This has to happen in `before`
         *     rather than `after` — an `after` callback can only fill in a
         *     null result, it cannot overturn a granted one.
         *
         *  2. The super administrator is authorised for everything, so newly
         *     added permissions reach it without a re-seed.
         *
         * Returning null for everyone else lets the normal policy and
         * permission checks proceed.
         */
        Gate::before(function (User $user, string $ability): ?bool {
            if (! $user->is_active) {
                return false;
            }

            return $user->hasRole(Roles::SUPER_ADMIN) ? true : null;
        });

        // Coarse gate used by the admin layout and route middleware.
        Gate::define('access-admin', fn (User $user) => $user->canAccessAdmin());
    }
}
