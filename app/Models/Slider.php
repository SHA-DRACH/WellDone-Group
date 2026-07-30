<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** A homepage hero slide. */
class Slider extends Model
{
    use HasFactory;
    use HasMediaPaths;

    protected $fillable = [
        'eyebrow', 'title', 'highlight', 'slogan', 'description', 'image', 'image_alt',
        'primary_button_text', 'primary_button_url',
        'secondary_button_text', 'secondary_button_url',
        'sort_order', 'is_active',
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
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image, asset('images/placeholders/hero.svg'));
    }
}
