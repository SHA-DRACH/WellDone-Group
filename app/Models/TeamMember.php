<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;

    protected $fillable = [
        'division_id', 'name', 'slug', 'position', 'department', 'short_bio', 'bio',
        'photo', 'email', 'phone', 'linkedin_url', 'twitter_url',
        'sort_order', 'is_leadership', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_leadership' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLeadership(Builder $query): Builder
    {
        return $query->where('is_leadership', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->photo, asset('images/placeholders/avatar.svg'));
    }
}
