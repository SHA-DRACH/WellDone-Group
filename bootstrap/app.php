<?php

use App\Http\Middleware\EnsureCanAccessAdmin;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetSecurityHeaders;
use App\Http\Middleware\TrackPageView;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetSecurityHeaders::class,
            EnsureUserIsActive::class,
            // After the active check — a deactivated account should be shown
            // the door rather than a password form.
            EnsurePasswordIsChanged::class,
            TrackPageView::class,
        ]);

        $middleware->alias([
            'admin' => EnsureCanAccessAdmin::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Public form endpoints are rate limited by named limiters defined in
        // App\Providers\RouteServiceProvider.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * A stale CSRF token must never be able to trap somebody in a session
         * they cannot leave.
         *
         * The token lives as long as the session (SESSION_LIFETIME), so any
         * page left open longer than that carries a dead one. On an ordinary
         * form that is a nuisance; on the sign-out form it is a security
         * problem, because the remedy for "I think somebody else is using my
         * account" is exactly the button that has stopped working.
         *
         * Sign-out is therefore honoured regardless of the token. The only
         * thing a forged request achieves here is ending a session, which is
         * the safe direction to fail in — and the session is genuinely ended,
         * not merely reported as ended.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            /*
             * Matched on the status rather than on TokenMismatchException:
             * the handler rewrites that exception into a 419 HttpException
             * before render callbacks are consulted, so a type hint on the
             * original class never fires.
             */
            $expired = $e instanceof TokenMismatchException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 419);

            if (! $expired || ! $request->routeIs('logout')) {
                return null;
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $request->expectsJson()
                ? response()->json(['message' => 'Signed out.'])
                : redirect()->route('login')->with('status', 'You have been signed out.');
        });

        /*
         * Never leak internal detail to a visitor. Laravel already renders
         * the error views in production; this only makes sure JSON clients
         * receive a generic message too.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            /*
             * Laravel's own responses for these already carry the right status
             * and, for a validation failure, the per-field errors the client
             * needs. Flattening them to a bare message here would turn every
             * rejected XHR form into an unexplained 500.
             */
            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return response()->json([
                'message' => $status === 500 && ! config('app.debug')
                    ? 'An unexpected error occurred. Please try again.'
                    : $e->getMessage(),
            ], $status);
        });
    })->create();
