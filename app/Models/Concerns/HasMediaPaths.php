<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves stored relative media paths into public URLs, tolerating both
 * uploaded files (on the `public` disk) and absolute URLs supplied by an
 * editor or seeder.
 */
trait HasMediaPaths
{
    public function mediaUrl(?string $path, ?string $fallback = null): ?string
    {
        if (blank($path)) {
            return $fallback;
        }

        if (Str::startsWith($path, ['http://', 'https://', '//', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function deleteMedia(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '//', '/'])) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
