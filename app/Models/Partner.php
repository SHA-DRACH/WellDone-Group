<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Partner extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;
    use LogsActivity;

    public const TYPES = ['partner', 'supplier', 'accreditation', 'affiliation'];

    protected $fillable = [
        'name', 'slug', 'logo', 'website', 'description', 'type',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('partner');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->logo, asset('images/placeholders/logo.svg'));
    }
}
