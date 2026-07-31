<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gallery extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;

    protected string $slugSource = 'title';

    protected $fillable = [
        'division_id', 'title', 'slug', 'description', 'cover_image',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /**
     * Falls back to the first photo when no cover has been set — but only if
     * the images relation is already loaded. Reaching for it otherwise would
     * fire a query per row on any list screen.
     */
    public function getCoverUrlAttribute(): ?string
    {
        if ($url = $this->mediaUrl($this->cover_image)) {
            return $url;
        }

        return $this->relationLoaded('images')
            ? $this->images->first()?->url
            : null;
    }

    public function getUrlAttribute(): string
    {
        return route('site.gallery.show', $this->slug);
    }
}
