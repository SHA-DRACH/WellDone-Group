<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
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

class Project extends Model
{
    use ScopedToDivision;
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;
    use LogsActivity;
    use SoftDeletes;

    /** @var string Column the slug is derived from. */
    protected string $slugSource = 'title';

    public const STATUSES = ['upcoming', 'ongoing', 'completed', 'on_hold'];

    protected $fillable = [
        'division_id', 'client_id', 'title', 'slug', 'summary', 'description',
        'scope_of_work', 'featured_image', 'location', 'client_display_name',
        'show_client', 'contract_value', 'currency', 'show_value', 'status',
        'start_date', 'end_date', 'completion_percentage', 'key_facts',
        'sort_order', 'is_featured', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'key_facts' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'show_client' => 'boolean',
            'show_value' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'contract_value' => 'decimal:2',
            'completion_percentage' => 'integer',
            'sort_order' => 'integer',
            'views' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'division_id', 'client_id', 'status', 'is_active', 'is_featured'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('project');
    }

    // -------------------------------------------------- Relationships

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
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

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            $status && in_array($status, self::STATUSES, true),
            fn (Builder $q) => $q->where('status', $status)
        );
    }

    public function scopeForDivision(Builder $query, ?string $slug): Builder
    {
        return $query->when($slug, fn (Builder $q) => $q->whereHas(
            'division',
            fn (Builder $d) => $d->where('slug', $slug)
        ));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $like = '%'.$term.'%';

            $q->where(fn (Builder $inner) => $inner
                ->where('title', 'like', $like)
                ->orWhere('summary', 'like', $like)
                ->orWhere('location', 'like', $like));
        });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('end_date')
            ->orderByDesc('id');
    }

    // -------------------------------------------------- Accessors & helpers

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->featured_image, asset('images/placeholders/project.svg'));
    }

    public function getUrlAttribute(): string
    {
        return route('site.projects.show', $this->slug);
    }

    /**
     * Client name to render publicly — respects the per-project and
     * per-client confidentiality flags.
     */
    public function getPublicClientNameAttribute(): ?string
    {
        if (! $this->show_client) {
            return null;
        }

        if ($this->client && $this->client->is_public) {
            return $this->client->name;
        }

        return $this->client_display_name;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'upcoming' => 'Upcoming',
            'ongoing' => 'Ongoing',
            'on_hold' => 'On Hold',
            default => 'Completed',
        };
    }

    public function getDurationLabelAttribute(): ?string
    {
        if (! $this->start_date) {
            return null;
        }

        $end = $this->end_date?->format('M Y') ?? 'Present';

        return $this->start_date->format('M Y').' – '.$end;
    }

    public function recordView(): void
    {
        // increment() bypasses model events, so no spurious activity-log entry.
        $this->newQuery()->whereKey($this->getKey())->increment('views');
    }
}
