<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\ScopedToDivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Service extends Model
{
    use ScopedToDivision;
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'division_id', 'name', 'slug', 'icon', 'image', 'summary', 'description',
        'features', 'sort_order', 'is_active', 'is_featured', 'is_quotable',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_quotable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'division_id', 'is_active', 'is_featured'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('service');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function quoteRequests(): BelongsToMany
    {
        return $this->belongsToMany(QuoteRequest::class, 'quote_request_service');
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeQuotable(Builder $query): Builder
    {
        return $query->where('is_quotable', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image);
    }

    public function getUrlAttribute(): string
    {
        return route('site.services.show', $this->slug);
    }
}
