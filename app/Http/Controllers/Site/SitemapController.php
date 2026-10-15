<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Career;
use App\Models\Division;
use App\Models\Gallery;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = Cache::remember('welldone.sitemap', now()->addHours(6), fn () => $this->urls());

        return response()
            ->view('site.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /staff',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /password',
            'Disallow: /request-a-quote/received',
            'Disallow: /newsletter',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines))->header('Content-Type', 'text/plain');
    }

    /** @return array<int, array{loc: string, lastmod: ?string, priority: string, changefreq: string}> */
    protected function urls(): array
    {
        $urls = [];

        $static = [
            ['site.home', '1.0', 'weekly'],
            ['site.about', '0.8', 'monthly'],
            ['site.leadership', '0.6', 'monthly'],
            ['site.csr', '0.6', 'monthly'],
            ['site.certifications', '0.5', 'yearly'],
            ['site.divisions.index', '0.9', 'monthly'],
            ['site.services.index', '0.9', 'monthly'],
            ['site.projects.index', '0.8', 'weekly'],
            ['site.gallery.index', '0.5', 'monthly'],
            ['site.blog.index', '0.8', 'daily'],
            ['site.careers.index', '0.7', 'daily'],
            ['site.downloads.index', '0.5', 'monthly'],
            ['site.faqs', '0.5', 'monthly'],
            ['site.contact', '0.7', 'yearly'],
            ['site.quote.create', '0.9', 'yearly'],
        ];

        foreach ($static as [$name, $priority, $frequency]) {
            $urls[] = [
                'loc' => route($name),
                'lastmod' => null,
                'priority' => $priority,
                'changefreq' => $frequency,
            ];
        }

        $collections = [
            [Division::query()->active()->get(['slug', 'updated_at']), 'site.divisions.show', '0.8', 'monthly'],
            [Service::query()->active()->get(['slug', 'updated_at']), 'site.services.show', '0.7', 'monthly'],
            [Project::query()->active()->get(['slug', 'updated_at']), 'site.projects.show', '0.7', 'monthly'],
            [Blog::query()->published()->get(['slug', 'updated_at']), 'site.blog.show', '0.6', 'weekly'],
            [Career::query()->open()->get(['slug', 'updated_at']), 'site.careers.show', '0.6', 'daily'],
            [Gallery::query()->active()->get(['slug', 'updated_at']), 'site.gallery.show', '0.4', 'monthly'],
        ];

        foreach ($collections as [$records, $routeName, $priority, $frequency]) {
            foreach ($records as $record) {
                $urls[] = [
                    'loc' => route($routeName, $record->slug),
                    'lastmod' => $record->updated_at?->toAtomString(),
                    'priority' => $priority,
                    'changefreq' => $frequency,
                ];
            }
        }

        return $urls;
    }
}
