<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Named rate limiters for the public form endpoints.
 *
 * Every limiter keys on IP address, since these endpoints are reachable
 * without signing in. The limits are deliberately generous enough for a
 * shared office NAT but low enough to make scripted abuse impractical.
 */
class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('quote-request', fn (Request $request) => [
            Limit::perHour(5)->by($request->ip()),
            Limit::perDay(15)->by($request->ip()),
        ]);

        RateLimiter::for('contact-form', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(12)->by($request->ip()),
        ]);

        RateLimiter::for('job-application', fn (Request $request) => [
            Limit::perHour(5)->by($request->ip()),
            Limit::perDay(20)->by($request->ip()),
        ]);

        RateLimiter::for('newsletter', fn (Request $request) => Limit::perHour(6)->by($request->ip()));

        RateLimiter::for('comment', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

        // Sign-in attempts are additionally throttled per-credential by
        // Laravel's ThrottlesLogins trait; this is the coarse per-IP ceiling.
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('downloads', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        /*
         * The site assistant. A real conversation is a handful of questions a
         * minute at most; the hourly ceiling is what stops a script from
         * running up a language-model bill on our account.
         */
        RateLimiter::for('assistant', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perHour(80)->by($request->ip()),
        ]);

        /*
         * One-time codes. OtpService already caps how many codes an address
         * may receive; this is the per-IP ceiling that stops one machine
         * cycling through many addresses, and limits guessing attempts.
         */
        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perHour(40)->by($request->ip()),
        ]);
    }
}
