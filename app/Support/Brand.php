<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves the brand marks used across the site, admin panel and emails.
 *
 * A setting may hold either a root-relative path to a file shipped in
 * public/ (the default) or a public-disk key uploaded from the CMS. Files
 * shipped in public/ are existence-checked so a missing artwork file falls
 * back to the built-in SVG mark rather than rendering a broken image.
 */
final class Brand
{
    /** Main logo — header, footer, sign-in, emails. */
    public static function logo(): string
    {
        return self::resolve('company.logo') ?? self::fallbackMark();
    }

    /** Variant for dark backgrounds; falls back to the main logo. */
    public static function logoLight(): string
    {
        return self::resolve('company.logo_light') ?? self::logo();
    }

    /** Square mark for the browser tab; falls back to the main logo. */
    public static function favicon(): string
    {
        return self::resolve('company.favicon') ?? self::resolve('company.logo') ?? self::fallbackMark();
    }

    /** True when real artwork is present, rather than the built-in fallback. */
    public static function hasLogo(): bool
    {
        return self::resolve('company.logo') !== null;
    }

    private static function resolve(string $key): ?string
    {
        $value = setting($key);

        if (blank($value)) {
            return self::discover();
        }

        if (Str::startsWith($value, ['http://', 'https://', '//'])) {
            return $value;
        }

        // Shipped asset: only use it if the file is actually on disk,
        // otherwise fall back to whatever artwork is in the brand folder.
        if (Str::startsWith($value, '/')) {
            return file_exists(public_path(ltrim($value, '/')))
                ? asset(ltrim($value, '/'))
                : self::discover();
        }

        // Uploaded through the CMS. Absolutised because the logo is also used
        // in email templates, and mail clients cannot resolve a root-relative
        // path — it would render as a broken image in every message.
        return Storage::disk('public')->exists($value)
            ? url(Storage::disk('public')->url($value))
            : self::discover();
    }

    /**
     * Last resort: use whatever image has been dropped into
     * public/images/brand. This means the logo works as soon as the file is
     * saved, whatever it happens to be called, without anyone having to
     * match a filename or edit a setting first.
     */
    private static function discover(): ?string
    {
        static $found;

        if ($found !== null) {
            return $found ?: null;
        }

        $directory = public_path('images/brand');

        if (! is_dir($directory)) {
            return ($found = false) ? null : null;
        }

        foreach (['png', 'svg', 'webp', 'jpg', 'jpeg'] as $extension) {
            $matches = glob($directory.'/*.'.$extension) ?: [];

            if ($matches !== []) {
                // Prefer a file named like a logo when several are present.
                usort($matches, fn ($a, $b) => Str::contains(basename($a), 'logo') ? -1 : 1);

                $found = asset('images/brand/'.basename($matches[0]));

                return $found;
            }
        }

        $found = false;

        return null;
    }

    private static function fallbackMark(): string
    {
        return asset('images/favicon.svg');
    }
}
