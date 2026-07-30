<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A downloadable document (company profile, brochure, catalogue).
 */
class Download extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;
    use LogsActivity;

    protected string $slugSource = 'title';

    protected $fillable = [
        'division_id', 'title', 'slug', 'description', 'category', 'file_path',
        'file_name', 'mime_type', 'size', 'thumbnail', 'requires_details',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_details' => 'boolean',
            'is_active' => 'boolean',
            'size' => 'integer',
            'download_count' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'category', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('download');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        return $query->when($category, fn (Builder $q) => $q->where('category', $category));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->thumbnail);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }

    public function getExtensionAttribute(): string
    {
        return strtoupper(pathinfo((string) $this->file_name, PATHINFO_EXTENSION) ?: 'FILE');
    }

    public function recordDownload(): void
    {
        $this->newQuery()->whereKey($this->getKey())->increment('download_count');
    }
}
