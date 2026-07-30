<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value site configuration.
 *
 * Reads go through a single cached map (see App\Support\Settings) rather than
 * hitting this model directly, so a page render costs at most one query.
 */
class Setting extends Model
{
    use HasFactory;

    public const CACHE_KEY = 'welldone.settings';

    protected $fillable = [
        'group', 'key', 'value', 'type', 'label', 'description',
        'options', 'sort_order', 'is_public',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Any write invalidates the cached map.
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    /** Cast the stored string into the type declared on the row. */
    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'json' => json_decode((string) $this->value, true) ?: [],
            default => $this->value,
        };
    }
}
