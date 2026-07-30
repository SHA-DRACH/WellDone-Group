<?php

use App\Services\SeoManager;
use App\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Read a site setting, e.g. setting('company.phone_primary').
     *
     * Called with no arguments it returns the Settings service itself, which
     * is handy for `setting()->group('social')`.
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        /** @var Settings $settings */
        $settings = app(Settings::class);

        if ($key === null) {
            return $settings;
        }

        return $settings->get($key, $default);
    }
}

if (! function_exists('seo')) {
    /**
     * The per-request SEO manager. Controllers call seo()->set([...]) and the
     * site layout calls seo()->resolve() to render the meta block.
     */
    function seo(): SeoManager
    {
        return app(SeoManager::class);
    }
}

if (! function_exists('brand')) {
    /**
     * Brand mark URLs with graceful fallback:
     * brand('logo'), brand('logo_light'), brand('favicon').
     */
    function brand(string $mark = 'logo'): string
    {
        return match ($mark) {
            'logo_light' => \App\Support\Brand::logoLight(),
            'favicon' => \App\Support\Brand::favicon(),
            default => \App\Support\Brand::logo(),
        };
    }
}

if (! function_exists('active_when')) {
    /**
     * Emit a CSS class when the current request matches one of the given
     * route-name patterns. Used by the site and admin navigation.
     */
    function active_when(array|string $patterns, string $class = 'active'): string
    {
        return request()->routeIs(...(array) $patterns) ? $class : '';
    }
}
