<?php

namespace App\Services;

use App\Models\Seo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the meta tags for the page currently being rendered.
 *
 * Precedence, most specific first:
 *   1. values set explicitly by a controller (`SeoManager::set()`)
 *   2. a `seo` row attached to the model being displayed
 *   3. a `seo` row attached to the current route name
 *   4. the site-wide defaults in settings
 */
class SeoManager
{
    protected array $overrides = [];

    protected ?Model $model = null;

    public function set(array $values): static
    {
        $this->overrides = array_merge($this->overrides, array_filter(
            $values,
            fn ($v) => $v !== null && $v !== ''
        ));

        return $this;
    }

    public function forModel(?Model $model): static
    {
        $this->model = $model;

        return $this;
    }

    /** @return array<string, mixed> */
    public function resolve(?string $routeName = null): array
    {
        $routeName ??= request()->route()?->getName();

        $fromModel = $this->modelMeta();
        $fromRoute = $this->routeMeta($routeName);

        $title = $this->pick('title', $fromModel, $fromRoute)
            ?? setting('seo.default_title', config('app.name'));

        $description = $this->pick('description', $fromModel, $fromRoute)
            ?? setting('seo.default_description');

        $image = $this->pick('og_image', $fromModel, $fromRoute)
            ?? setting('seo.default_og_image');

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $this->pick('keywords', $fromModel, $fromRoute) ?? setting('seo.default_keywords'),
            'og_title' => $this->pick('og_title', $fromModel, $fromRoute) ?? $title,
            'og_description' => $this->pick('og_description', $fromModel, $fromRoute) ?? $description,
            'og_image' => $image ? $this->absoluteUrl($image) : null,
            'canonical' => $this->pick('canonical_url', $fromModel, $fromRoute) ?? url()->current(),
            'robots' => $this->pick('robots', $fromModel, $fromRoute) ?? 'index, follow',
            'schema' => $this->overrides['schema'] ?? null,
        ];
    }

    protected function pick(string $key, ?array $model, ?array $route): mixed
    {
        foreach ([$this->overrides, $model, $route] as $source) {
            if (! empty($source[$key])) {
                return $source[$key];
            }
        }

        return null;
    }

    protected function modelMeta(): ?array
    {
        if (! $this->model) {
            return null;
        }

        // Use the relation if the controller already eager-loaded it;
        // otherwise fetch it once rather than lazily per attribute.
        if ($this->model->relationLoaded('seo')) {
            $seo = $this->model->getRelation('seo');
        } elseif (method_exists($this->model, 'seo')) {
            $seo = $this->model->seo()->first();
        } else {
            return null;
        }

        return $seo?->only([
            'title', 'description', 'keywords', 'og_title',
            'og_description', 'og_image', 'canonical_url', 'robots',
        ]);
    }

    protected function routeMeta(?string $routeName): ?array
    {
        if (! $routeName) {
            return null;
        }

        $map = Cache::remember('welldone.seo.routes', now()->addHours(6), function () {
            return Seo::query()
                ->whereNotNull('route_name')
                ->get()
                ->keyBy('route_name')
                ->map(fn (Seo $seo) => $seo->only([
                    'title', 'description', 'keywords', 'og_title',
                    'og_description', 'og_image', 'canonical_url', 'robots',
                ]))
                ->all();
        });

        return $map[$routeName] ?? null;
    }

    protected function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return url(str_starts_with($path, '/') ? $path : \Illuminate\Support\Facades\Storage::disk('public')->url($path));
    }

    public static function flushCache(): void
    {
        Cache::forget('welldone.seo.routes');
    }
}
