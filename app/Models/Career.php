<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\ScopedToDivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A published vacancy or internship.
 */
class Career extends Model
{
    use ScopedToDivision;
    use HasFactory;
    use HasSlug;
    use LogsActivity;
    use SoftDeletes;

    protected string $slugSource = 'title';

    public const EMPLOYMENT_TYPES = ['full_time', 'part_time', 'contract', 'internship', 'temporary'];

    public const EXPERIENCE_LEVELS = ['entry', 'junior', 'mid', 'senior', 'lead', 'executive'];

    protected $fillable = [
        'division_id', 'title', 'slug', 'department', 'location', 'employment_type',
        'experience_level', 'description', 'responsibilities', 'requirements',
        'benefits', 'salary_range', 'positions_available', 'opens_at', 'closes_at',
        'is_featured', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opens_at' => 'date',
            'closes_at' => 'date',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'positions_available' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'division_id', 'is_active', 'closes_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('career');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    /** Live vacancies: active, already open and not past their closing date. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('opens_at')->orWhere('opens_at', '<=', today()))
            ->where(fn (Builder $q) => $q->whereNull('closes_at')->orWhere('closes_at', '>=', today()));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->is_active
            && ($this->opens_at === null || $this->opens_at->lte(today()))
            && ($this->closes_at === null || $this->closes_at->gte(today()));
    }

    public function getEmploymentTypeLabelAttribute(): string
    {
        return str($this->employment_type)->replace('_', ' ')->title()->toString();
    }

    public function getExperienceLevelLabelAttribute(): string
    {
        return str($this->experience_level)->title()->toString();
    }

    public function getUrlAttribute(): string
    {
        return route('site.careers.show', $this->slug);
    }
}
