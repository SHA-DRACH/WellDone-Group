<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A corporate social responsibility programme. */
class CsrInitiative extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;

    protected string $slugSource = 'title';

    protected $fillable = [
        'title', 'slug', 'focus_area', 'summary', 'description', 'image',
        'location', 'year', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer', 'year' => 'integer'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('year');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image, asset('images/placeholders/csr.svg'));
    }
}
