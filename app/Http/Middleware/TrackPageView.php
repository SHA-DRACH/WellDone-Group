<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records a first-party page view for the dashboard traffic widget.
 *
 * Writes happen after the response is sent so the visitor never waits on the
 * insert. The visitor identifier is a salted hash of IP + user agent — enough
 * to distinguish sessions for a unique-visitor count, but not reversible to
 * an address.
 */
class TrackPageView
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldTrack($request, $response)) {
            return;
        }

        try {
            PageView::query()->create([
                'path' => Str::limit($request->path(), 490, ''),
                'route_name' => $request->route()?->getName(),
                'referrer' => Str::limit((string) $request->headers->get('referer'), 490, '') ?: null,
                'visitor_hash' => $this->visitorHash($request),
                'device' => $this->device($request),
                'viewed_at' => now(),
            ]);
        } catch (\Throwable) {
            // Analytics must never break a page render.
        }
    }

    protected function shouldTrack(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->wantsJson()) {
            return false;
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        // Public pages only — admin activity is covered by the audit log.
        if ($request->is('admin', 'admin/*', 'login', 'register', 'password/*', 'up', 'storage/*')) {
            return false;
        }

        return ! $this->isBot((string) $request->userAgent());
    }

    protected function visitorHash(Request $request): string
    {
        return hash_hmac(
            'sha256',
            $request->ip().'|'.$request->userAgent().'|'.now()->toDateString(),
            (string) Config::get('app.key')
        );
    }

    protected function device(Request $request): string
    {
        $agent = Str::lower((string) $request->userAgent());

        return match (true) {
            Str::contains($agent, ['ipad', 'tablet', 'kindle']) => 'tablet',
            Str::contains($agent, ['mobi', 'android', 'iphone']) => 'mobile',
            default => 'desktop',
        };
    }

    protected function isBot(string $agent): bool
    {
        return Str::contains(Str::lower($agent), [
            'bot', 'crawl', 'spider', 'slurp', 'curl', 'wget', 'headless', 'monitor', 'preview',
        ]);
    }
}
