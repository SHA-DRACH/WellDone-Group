<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Client;
use App\Models\Division;
use App\Models\Partner;
use App\Models\Project;
use App\Models\Slider;
use App\Models\Testimonial;
use App\Support\Video\Mp4Duration;
use App\Support\Video\VideoEmbed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HomeController extends Controller
{
    public function index(): View
    {
        /*
         * The homepage is the most-hit page on the site and its content
         * changes rarely, so the whole query set is cached. Writes in the CMS
         * clear `welldone.home.*` via the FlushesSiteCache trait.
         */
        $data = Cache::remember('welldone.home.blocks', now()->addHours(2), function () {
            return [
                'slides' => Slider::query()->active()->ordered()->get(),

                'divisions' => Division::query()
                    ->active()
                    ->ordered()
                    ->withCount(['projects' => fn ($q) => $q->where('is_active', true)])
                    ->get(),

                'projects' => Project::query()
                    ->active()
                    ->with('division:id,name,slug')
                    ->ordered()
                    ->limit(6)
                    ->get(),

                'testimonials' => Testimonial::query()->active()->ordered()->limit(9)->get(),

                'partners' => Partner::query()->active()->ordered()->limit(14)->get(),

                'clients' => Client::query()->active()->public()->featured()->ordered()->limit(12)->get(),

                'posts' => Blog::query()
                    ->published()
                    ->with(['category:id,name,slug,color', 'author:id,name'])
                    ->latest('published_at')
                    ->limit(3)
                    ->get(),
            ];
        });

        seo()->set([
            'title' => setting('seo.default_title'),
            'description' => setting('seo.default_description'),
            'schema' => $this->organisationSchema(),
        ]);

        return view('site.home', $data + ['welcomeVideo' => $this->welcomeVideo()]);
    }

    /**
     * The welcome film, if one has been added in Website Settings → Home.
     * An uploaded file takes precedence over a YouTube/Vimeo link.
     *
     * @return array<string, mixed>|null
     */
    protected function welcomeVideo(): ?array
    {
        $file = setting('home.welcome_video');
        $embed = VideoEmbed::from(setting('home.welcome_video_url'));

        if (blank($file) && $embed === null) {
            return null;
        }

        $poster = setting('home.welcome_video_poster');

        return [
            'src' => filled($file) ? Storage::disk('public')->url($file) : null,
            'embed' => filled($file) ? null : $embed,
            'poster' => filled($poster) ? Storage::disk('public')->url($poster) : null,
            'heading' => setting('home.welcome_video_heading', 'Welcome to WellDone Group'),
            'body' => setting('home.welcome_video_body'),
            // Read from the file's own header once, then remembered by path.
            'duration' => filled($file)
                ? Cache::rememberForever('welldone.video.length.'.md5($file), fn () => ($s = Mp4Duration::seconds(Storage::disk('public')->path($file))) === null ? null : (int) round($s))
                : null,
        ];
    }

    /** schema.org Organization markup for the homepage. */
    protected function organisationSchema(): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Corporation',
            'name' => setting('company.name'),
            'legalName' => setting('company.legal_name'),
            'url' => url('/'),
            'slogan' => setting('company.slogan'),
            'description' => setting('company.about_short'),
            'foundingDate' => setting('company.founded_year'),
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => setting('contact.address_line1'),
                'addressLocality' => setting('contact.city'),
                'addressCountry' => setting('contact.country'),
                'postalCode' => setting('contact.postal_code'),
            ]),
            'telephone' => setting('contact.phone_primary'),
            'email' => setting('contact.email_general'),
            'sameAs' => array_values(array_filter([
                setting('social.facebook'),
                setting('social.linkedin'),
                setting('social.twitter'),
                setting('social.instagram'),
                setting('social.youtube'),
            ])),
        ]);
    }
}
