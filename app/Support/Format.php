<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Small presentation helpers shared by Blade views. */
final class Format
{
    public static function money(int|float|string|null $amount, string $currency = 'USD'): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        $symbol = match ($currency) {
            'USD' => '$',
            'LRD' => 'L$',
            'EUR' => '€',
            'GBP' => '£',
            default => $currency.' ',
        };

        return $symbol.number_format((float) $amount, 2);
    }

    /** Compact display for large counts: 1,240 → 1.2k. */
    public static function compact(int $value): string
    {
        return match (true) {
            $value >= 1_000_000 => round($value / 1_000_000, 1).'m',
            $value >= 1_000 => round($value / 1_000, 1).'k',
            default => (string) $value,
        };
    }

    /** Plain-text preview of rich-text content. */
    public static function excerpt(?string $html, int $length = 160): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $html))), $length);
    }

    public static function fileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
