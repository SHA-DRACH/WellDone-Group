<?php

use App\Http\Middleware\EnsureCanAccessAdmin;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetSecurityHeaders;
use App\Http\Middleware\TrackPageView;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
            TrackPageView::class,
        ]);

        $middleware->alias([
            'admin' => EnsureCanAccessAdmin::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Public form endpoints are rate limited by named limiters defined in
        // App\Providers\RouteServiceProvider.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Never leak internal detail to a visitor. Laravel already renders
         * the error views in production; this only makes sure JSON clients
         * receive a generic message too.
         */
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
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
