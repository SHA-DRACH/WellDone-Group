<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Testimonial extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use LogsActivity;

    protected $fillable = [
        'client_id', 'division_id', 'author_name', 'author_title', 'company',
        'avatar', 'quote', 'rating', 'sort_order', 'is_featured', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['author_name', 'company', 'is_active', 'is_featured'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('testimonial');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->avatar);
    }

    public function getInitialsAttribute(): string
    {
        return Str::of($this->author_name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
