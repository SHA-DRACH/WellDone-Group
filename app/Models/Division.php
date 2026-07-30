<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A WellDone Group business division (Mining & Quarry, Procurement &
 * Logistics, Medical Equipment, ICT, and any future subsidiary).
 */
class Division extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'tagline', 'icon', 'accent_color', 'summary', 'description',
        'capabilities', 'hero_image', 'thumbnail', 'brochure_path', 'highlights',
        'sort_order', 'is_active', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'is_active', 'is_featured', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('division');
    }

    // -------------------------------------------------- Relationships

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('sort_order');
    }

    public function activeServices(): HasMany
    {
        return $this->services()->where('is_active', true);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function careers(): HasMany
    {
        return $this->hasMany(Career::class);
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(Gallery::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    // -------------------------------------------------- Scopes

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
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // -------------------------------------------------- Accessors

    public function getHeroUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->hero_image, asset('images/placeholders/division-hero.svg'));
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->thumbnail, asset('images/placeholders/division-card.svg'));
    }

    public function getBrochureUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->brochure_path);
    }

    public function getUrlAttribute(): string
    {
        return route('site.divisions.show', $this->slug);
    }
}
