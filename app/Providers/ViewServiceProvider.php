<?php

namespace App\Providers;

use App\Models\Division;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * View composers and Blade directives shared across the public site.
 */
class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * The header mega menu and footer both need every active division
         * with its services. This is stable content, so it is cached and the
         * cache is cleared whenever a division or service is written.
         */
        View::composer(['site.partials.header', 'site.partials.footer'], function ($view) {
            $view->with('navDivisions', Cache::remember(
                'welldone.nav.divisions',
                now()->addHours(6),
                fn () => Division::query()
                    ->active()
                    ->ordered()
                    ->with(['activeServices' => fn ($q) => $q->select('id', 'division_id', 'name', 'slug', 'icon', 'summary')->limit(8)])
                    ->get(['id', 'name', 'slug', 'icon', 'tagline', 'summary'])
            ));
        });

        /*
         * @money(1500) → $1,500.00 — used on quote and project screens.
         */
        Blade::directive('money', function (string $expression) {
            return "<?php echo \App\Support\Format::money({$expression}); ?>";
        });

        /*
         * @setting('company.name') is shorthand for the escaped helper call.
         */
        Blade::directive('setting', function (string $expression) {
            return "<?php echo e(setting({$expression})); ?>";
        });
    }
}
