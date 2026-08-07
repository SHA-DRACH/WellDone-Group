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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * An internal notice for staff. Never rendered on the public website.
 */
class Announcement extends Model
{
    use ScopedToDivision;
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;
    use LogsActivity;
    use SoftDeletes;

    protected string $slugSource = 'title';

    public const CATEGORIES = [
        'general', 'holiday', 'meeting', 'promotion', 'birthday',
        'event', 'policy', 'training', 'urgent',
    ];

    protected $fillable = [
        'title', 'slug', 'body', 'excerpt', 'category',
        'department_id', 'division_id', 'published_by', 'attachment',
        'is_pinned', 'notify_by_email', 'published_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_pinned' => 'boolean',
            'notify_by_email' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Announcement $announcement): void {
            $announcement->published_at ??= now();

            if (blank($announcement->excerpt) && filled($announcement->body)) {
                $announcement->excerpt = Str::limit(strip_tags($announcement->body), 200);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'category', 'department_id', 'is_active', 'is_pinned'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('announcement');
    }

    // -------------------------------------------------- Relationships

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at');
    }

    // -------------------------------------------------- Scopes

    /** Live now: active, published, and not yet expired. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    /**
     * Announcements this employee should see: company-wide ones, plus any
     * addressed to their own department or division.
     */
    public function scopeVisibleTo(Builder $query, ?Employee $employee): Builder
    {
        return $query->where(function (Builder $q) use ($employee) {
            $q->where(fn (Builder $general) => $general
                ->whereNull('department_id')
                ->whereNull('division_id'));

            if ($employee?->department_id) {
                $q->orWhere('department_id', $employee->department_id);
            }

            if ($employee?->division_id) {
                $q->orWhere('division_id', $employee->division_id);
            }
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('published_at');
    }

    // -------------------------------------------------- Accessors

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->attachment);
    }

    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'urgent' => 'danger',
            'holiday', 'event', 'birthday' => 'success',
            'policy' => 'primary',
            'training' => 'info',
            'promotion' => 'warning',
            default => 'secondary',
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /** Audience label for the admin list. */
    public function getAudienceAttribute(): string
    {
        if ($this->department_id) {
            return $this->department?->name ?? 'Department';
        }

        if ($this->division_id) {
            return $this->division?->name ?? 'Division';
        }

        return 'Everyone';
    }
}
