<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One application for time off.
 *
 * `days` is written once, when the request is lodged, and never recomputed.
 * A stored figure is what the approval, the balance and eventually payroll all
 * agree on; recalculating it later would let a change to the working-week
 * rules quietly rewrite leave somebody already took.
 */
class LeaveRequest extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use LogsActivity;
    use SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'reference', 'employee_id', 'leave_type_id',
        'start_date', 'end_date', 'days', 'is_half_day',
        'reason', 'contact_during_leave', 'document_path',
        'status', 'reviewed_by', 'reviewed_at', 'review_notes',
        'covering_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days' => 'decimal:2',
            'is_half_day' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LeaveRequest $request): void {
            $request->reference ??= static::nextReference();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reference', 'employee_id', 'leave_type_id', 'start_date', 'end_date', 'days', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('leave_request');
    }

    /** Sequential reference, e.g. WDG-LV-2026-0001. */
    public static function nextReference(): string
    {
        $prefix = 'WDG-LV-'.now()->year.'-';

        $last = static::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(reference) DESC, reference DESC')
            ->value('reference');

        $next = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------- Relationships

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function coveringEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'covering_employee_id');
    }

    // -------------------------------------------------- Scopes

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /** Requests that overlap the given span, whatever their status. */
    public function scopeOverlapping(Builder $query, $start, $end): Builder
    {
        return $query->where('start_date', '<=', $end)->where('end_date', '>=', $start);
    }

    /** Approved leave in progress today. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->approved()
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now());
    }

    public function scopeForYear(Builder $query, ?int $year = null): Builder
    {
        return $query->whereYear('start_date', $year ?? now()->year);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('start_date');
    }

    // -------------------------------------------------- Accessors

    public function getStatusLabelAttribute(): string
    {
        return Str::title($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function getDocumentUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->document_path);
    }

    public function getPeriodLabelAttribute(): string
    {
        if ($this->start_date->isSameDay($this->end_date)) {
            return $this->start_date->format('j M Y').($this->is_half_day ? ' (half day)' : '');
        }

        return $this->start_date->format('j M').' – '.$this->end_date->format('j M Y');
    }

    // -------------------------------------------------- State

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /** Leave that has not started yet can still be withdrawn. */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'approved'], true)
            && $this->start_date->isFuture();
    }
}
