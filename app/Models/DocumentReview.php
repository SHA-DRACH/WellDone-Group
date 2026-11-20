<?php

namespace App\Models;

use App\Models\Concerns\ScopedToDivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A document a division has submitted for the CEO or Managing Director to
 * review — approved, rejected, or sent back for changes.
 */
class DocumentReview extends Model
{
    use ScopedToDivision;

    public const STATUSES = ['pending', 'approved', 'changes_requested', 'rejected'];

    /** What the reviewer can decide. */
    public const DECISIONS = ['approved', 'changes_requested', 'rejected'];

    public const CATEGORIES = [
        'report' => 'Report',
        'proposal' => 'Proposal',
        'contract' => 'Contract / agreement',
        'financial' => 'Financial document',
        'request' => 'Request for approval',
        'other' => 'Other',
    ];

    /** File types a division may submit, by extension. */
    public const FILE_TYPES = 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png';

    public const MAX_KB = 20480;

    protected $fillable = [
        'reference', 'division_id', 'submitted_by', 'title', 'category', 'notes',
        'file_path', 'original_name', 'file_size',
        'status', 'reviewed_by', 'reviewed_at', 'review_comment',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DocumentReview $review): void {
            $review->reference ??= 'WDG-DOC-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
            $review->status ??= 'pending';
        });
    }

    /**
     * Submissions always belong to a division; one with none recorded is not
     * a group record that every division should see.
     */
    public function unassignedBelongsToGroup(): bool
    {
        return false;
    }

    // -------------------------------------------------- Relationships

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // -------------------------------------------------- Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    // -------------------------------------------------- Accessors

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Awaiting review',
            'approved' => 'Approved',
            'changes_requested' => 'Changes requested',
            'rejected' => 'Rejected',
            default => Str::headline((string) $this->status),
        };
    }

    public function getStatusColourAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'changes_requested' => 'warning',
            'rejected' => 'danger',
            default => 'secondary',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? Str::headline((string) $this->category);
    }

    /** PDFs and images open in the browser; other types download. */
    public function getPreviewableAttribute(): bool
    {
        return in_array(strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png'], true);
    }
}
