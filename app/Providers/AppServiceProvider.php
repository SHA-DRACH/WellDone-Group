<?php

namespace App\Providers;

use App\Services\SeoManager;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
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

        // Catch N+1 queries and mass-assignment mistakes during development.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Keep polymorphic tables (SEO metadata, activity log) free of
        // fully-qualified class names so the namespace can be refactored
        // without a data migration. The map is enforced, so every model that
        // can appear on either side of a morph must be listed here.
        Relation::enforceMorphMap([
            'blog' => \App\Models\Blog::class,
            'blog-category' => \App\Models\BlogCategory::class,
            'blog-comment' => \App\Models\BlogComment::class,
            'career' => \App\Models\Career::class,
            'certificate' => \App\Models\Certificate::class,
            'client' => \App\Models\Client::class,
            'contact-message' => \App\Models\ContactMessage::class,
            'csr-initiative' => \App\Models\CsrInitiative::class,
            'division' => \App\Models\Division::class,
            'download' => \App\Models\Download::class,
            'faq' => \App\Models\Faq::class,
            'gallery' => \App\Models\Gallery::class,
            'gallery-image' => \App\Models\GalleryImage::class,
            'job-application' => \App\Models\JobApplication::class,
            'milestone' => \App\Models\Milestone::class,
            'newsletter' => \App\Models\Newsletter::class,
            'partner' => \App\Models\Partner::class,
            'project' => \App\Models\Project::class,
            'project-image' => \App\Models\ProjectImage::class,
            'quote-request' => \App\Models\QuoteRequest::class,
            'quote-request-file' => \App\Models\QuoteRequestFile::class,
            'service' => \App\Models\Service::class,
            'setting' => \App\Models\Setting::class,
            'slider' => \App\Models\Slider::class,
            'tag' => \App\Models\Tag::class,
            'team-member' => \App\Models\TeamMember::class,
            'testimonial' => \App\Models\Testimonial::class,
            'user' => \App\Models\User::class,
        ]);

        $this->flushSiteCacheOnContentChange();

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
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
            \App\Models\Blog::class,
            \App\Models\BlogCategory::class,
            \App\Models\Certificate::class,
            \App\Models\Client::class,
            \App\Models\CsrInitiative::class,
            \App\Models\Division::class,
            \App\Models\Download::class,
            \App\Models\Faq::class,
            \App\Models\Gallery::class,
            \App\Models\Milestone::class,
            \App\Models\Partner::class,
            \App\Models\Project::class,
            \App\Models\Seo::class,
            \App\Models\Service::class,
            \App\Models\Slider::class,
            \App\Models\TeamMember::class,
            \App\Models\Testimonial::class,
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
