<?php

namespace App\Providers;

use App\Listeners\EmbedBrandLogo;
use App\Mail\Transport\EmailJsTransport;
use App\Models\Announcement;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\Career;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\CsrInitiative;
use App\Models\Department;
use App\Models\Division;
use App\Models\Download;
use App\Models\Employee;
use App\Models\EmployeeBankDetail;
use App\Models\EmploymentType;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\JobApplication;
use App\Models\JobApplicationDocument;
use App\Models\Milestone;
use App\Models\Newsletter;
use App\Models\Partner;
use App\Models\Position;
use App\Models\Project;
use App\Models\ProjectImage;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestFile;
use App\Models\Seo;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Tag;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\SeoManager;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One cached settings map per request cycle.
        $this->app->singleton(Settings::class);

        // Meta tags are accumulated by controllers and read once by the layout.
        $this->app->singleton(SeoManager::class);
    }

    public function boot(): void
    {
        // Bootstrap 5 markup for all paginators.
        Paginator::useBootstrapFive();

        $this->registerEmailJsTransport();

        // Ships the logo inside every branded email, rather than linking to it.
        Event::listen(MessageSending::class, EmbedBrandLogo::class);

        // Catch N+1 queries and mass-assignment mistakes during development.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Keep polymorphic tables (SEO metadata, activity log) free of
        // fully-qualified class names so the namespace can be refactored
        // without a data migration. The map is enforced, so every model that
        // can appear on either side of a morph must be listed here.
        Relation::enforceMorphMap([
            'announcement' => Announcement::class,
            'blog' => Blog::class,
            'department' => Department::class,
            'employee' => Employee::class,
            'employee-bank-detail' => EmployeeBankDetail::class,
            'employment-type' => EmploymentType::class,
            'job-application-document' => JobApplicationDocument::class,
            'position' => Position::class,
            'blog-category' => BlogCategory::class,
            'blog-comment' => BlogComment::class,
            'career' => Career::class,
            'certificate' => Certificate::class,
            'client' => Client::class,
            'contact-message' => ContactMessage::class,
            'csr-initiative' => CsrInitiative::class,
            'division' => Division::class,
            'download' => Download::class,
            'faq' => Faq::class,
            'gallery' => Gallery::class,
            'gallery-image' => GalleryImage::class,
            'job-application' => JobApplication::class,
            'milestone' => Milestone::class,
            'newsletter' => Newsletter::class,
            'partner' => Partner::class,
            'project' => Project::class,
            'project-image' => ProjectImage::class,
            'quote-request' => QuoteRequest::class,
            'quote-request-file' => QuoteRequestFile::class,
            'service' => Service::class,
            'setting' => Setting::class,
            'slider' => Slider::class,
            'tag' => Tag::class,
            'team-member' => TeamMember::class,
            'testimonial' => Testimonial::class,
            'user' => User::class,
        ]);

        $this->flushSiteCacheOnContentChange();

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    /**
     * Makes `MAIL_MAILER=emailjs` available.
     *
     * Registered as a Symfony transport so every existing Mailable and
     * Notification works unchanged — swapping between EmailJS, SMTP and
     * Mailpit is a one-line change in .env.
     */
    protected function registerEmailJsTransport(): void
    {
        Mail::extend('emailjs', function (array $config) {
            $config = array_merge(config('services.emailjs', []), $config);

            foreach (['service_id', 'template_id', 'public_key', 'private_key'] as $key) {
                if (blank($config[$key] ?? null)) {
                    throw new \RuntimeException(
                        "EmailJS is missing its {$key}. Set EMAILJS_".strtoupper($key).' in .env.'
                    );
                }
            }

            return new EmailJsTransport(
                app(HttpClient::class),
                $config['service_id'],
                $config['template_id'],
                $config['public_key'],
                $config['private_key'],
                (int) ($config['timeout'] ?? 30),
            );
        });
    }

    /**
     * Public-site fragments (homepage blocks, the mega menu, route-level SEO)
     * are cached for hours. Registering the invalidation centrally keeps the
     * models themselves free of caching concerns, and means a new content
     * model only has to be added to one list.
     */
    protected function flushSiteCacheOnContentChange(): void
    {
        $models = [
            Blog::class,
            BlogCategory::class,
            Certificate::class,
            Client::class,
            CsrInitiative::class,
            Division::class,
            Download::class,
            Faq::class,
            Gallery::class,
            Milestone::class,
            Partner::class,
            Project::class,
            Seo::class,
            Service::class,
            Slider::class,
            TeamMember::class,
            Testimonial::class,
        ];

        $flush = function (): void {
            foreach (['welldone.home.blocks', 'welldone.nav.divisions', 'welldone.seo.routes'] as $key) {
                Cache::forget($key);
            }
        };

        foreach ($models as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }
}
