<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Gives a model a URL-friendly slug derived from a source column.
 *
 * The slug is generated on create when the field is left empty, and is kept
 * unique by appending an incrementing suffix. Once a record is published the
 * slug is never regenerated automatically — changing it would break inbound
 * links — but an editor can still set it explicitly.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            $slugField = $model->slugField();

            if (blank($model->{$slugField})) {
                $model->{$slugField} = $model->generateUniqueSlug(
                    (string) $model->{$model->slugSourceField()}
                );

                return;
            }

            // Normalise a hand-entered slug and keep it unique.
            if ($model->isDirty($slugField)) {
                $model->{$slugField} = $model->generateUniqueSlug((string) $model->{$slugField});
            }
        });
    }

    public function slugField(): string
    {
        return 'slug';
    }

    public function slugSourceField(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'name';
    }

    public function generateUniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $base = Str::limit($base, 180, '');
        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($slug)) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::query()
            ->where($this->slugField(), $slug)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()));

        if (method_exists(static::class, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query->exists();
    }

    public function getRouteKeyName(): string
    {
        return $this->slugField();
    }
}
