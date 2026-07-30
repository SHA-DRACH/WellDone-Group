<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** An entry on the company-history timeline. */
class Milestone extends Model
{
    use HasFactory;
    use HasMediaPaths;

    protected $fillable = [
        'year', 'title', 'description', 'image', 'icon', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('year');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image);
    }
}
