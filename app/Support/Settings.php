<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Cached access to the `settings` table.
 *
 * The whole table is small and read on nearly every request, so it is loaded
 * once per request cycle and cached forever; Setting's model events flush the
 * cache on write.
 *
 * Keys are addressed as `group.key`, e.g. `company.phone_primary`.
 */
class Settings
{
    /** @var array<string, mixed>|null */
    protected ?array $items = null;

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->items !== null) {
            return $this->items;
        }

        // Guard against boot-time access before migrations have run
        // (artisan migrate, package discovery, CI containers).
        if (! Schema::hasTable('settings')) {
            return $this->items = [];
        }

        return $this->items = Cache::rememberForever(Setting::CACHE_KEY, function (): array {
            return Setting::query()
                ->get()
                ->mapWithKeys(fn (Setting $s) => [$s->group.'.'.$s->key => $s->typed_value])
                ->all();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return blank($value) && $value !== false && $value !== 0 ? $default : $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** All settings within a group, keyed by the bare key. */
    public function group(string $group): array
    {
        $prefix = $group.'.';
        $out = [];

        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $out[substr($key, strlen($prefix))] = $value;
            }
        }

        return $out;
    }

    /**
     * Write a value, creating the row if the key is new. Returns the model so
     * callers can chain, e.g. when persisting an uploaded image path.
     */
    public function set(string $key, mixed $value, array $attributes = []): Setting
    {
        [$group, $name] = array_pad(explode('.', $key, 2), 2, null);

        if ($name === null) {
            [$group, $name] = ['general', $group];
        }

        $setting = Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $name],
            array_merge([
                'value' => is_array($value) ? json_encode($value) : $value,
                'label' => $attributes['label'] ?? str($name)->replace('_', ' ')->title()->toString(),
            ], $attributes)
        );

        $this->flush();

        return $setting;
    }

    public function flush(): void
    {
        $this->items = null;
        Cache::forget(Setting::CACHE_KEY);
    }
}
