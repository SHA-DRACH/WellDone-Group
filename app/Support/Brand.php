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
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//'])) {
            return $value;
        }

        // Shipped asset: only use it if the file is actually on disk.
        if (Str::startsWith($value, '/')) {
            return file_exists(public_path(ltrim($value, '/'))) ? asset(ltrim($value, '/')) : null;
        }

        // Uploaded through the CMS.
        return Storage::disk('public')->exists($value)
            ? Storage::disk('public')->url($value)
            : null;
    }

    private static function fallbackMark(): string
    {
        return asset('images/favicon.svg');
    }
}
