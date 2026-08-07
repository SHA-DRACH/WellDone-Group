<?php

namespace App\Models;

use App\Models\Concerns\ScopedToDivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An inbound request for quotation — the core lead record.
 */
class QuoteRequest extends Model
{
    use ScopedToDivision;
    use HasFactory;

    public const STATUSES = ['new', 'in_review', 'quoted', 'won', 'lost', 'closed'];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = [
        'reference', 'user_id', 'division_id', 'contact_name', 'company_name', 'email', 'phone',
        'country', 'city', 'project_title', 'description', 'budget_range', 'currency',
        'timeline', 'preferred_start_date', 'delivery_location', 'status', 'priority',
        'assigned_to', 'internal_notes', 'quoted_amount', 'quoted_at', 'responded_at',
        'source', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'preferred_start_date' => 'date',
            'quoted_at' => 'datetime',
            'responded_at' => 'datetime',
            'quoted_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteRequest $quote): void {
            $quote->reference ??= static::generateReference();
        });
    }

    public static function generateReference(): string
    {
        return 'WDG-RFQ-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }

    // -------------------------------------------------- Relationships

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'quote_request_service');
    }

    public function files(): HasMany
    {
        return $this->hasMany(QuoteRequestFile::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** The registered account that submitted this request, if any. */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -------------------------------------------------- Scopes

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            $status && in_array($status, self::STATUSES, true),
            fn (Builder $q) => $q->where('status', $status)
        );
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['new', 'in_review', 'quoted']);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $like = '%'.$term.'%';

            $q->where(fn (Builder $inner) => $inner
                ->where('reference', 'like', $like)
                ->orWhere('contact_name', 'like', $like)
                ->orWhere('company_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('project_title', 'like', $like));
        });
    }

    // -------------------------------------------------- Accessors

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'primary',
            'in_review' => 'info',
            'quoted' => 'warning',
            'won' => 'success',
            'lost' => 'danger',
            default => 'secondary',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'low' => 'secondary',
            default => 'info',
        };
    }

    /**
     * An enquiry that has not been routed yet belongs to nobody. Showing it
     * to all four divisions would be noise, not access.
     */
    public function unassignedBelongsToGroup(): bool
    {
        return false;
    }
}
