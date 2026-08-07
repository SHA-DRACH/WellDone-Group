<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one employee is entitled to, and has used, of one leave type in one
 * year.
 *
 * Days pending approval are held apart from days taken so that two requests
 * lodged in the same afternoon cannot both be granted out of a balance that
 * only covers one of them.
 */
class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_type_id', 'year',
        'entitled_days', 'carried_forward_days', 'adjustment_days',
        'taken_days', 'pending_days', 'adjustment_reason',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitled_days' => 'decimal:2',
            'carried_forward_days' => 'decimal:2',
            'adjustment_days' => 'decimal:2',
            'taken_days' => 'decimal:2',
            'pending_days' => 'decimal:2',
        ];
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

    // -------------------------------------------------- Scopes

    public function scopeForYear(Builder $query, ?int $year = null): Builder
    {
        return $query->where('year', $year ?? now()->year);
    }

    // -------------------------------------------------- Accessors

    /** Everything the employee may draw on this year. */
    public function getTotalDaysAttribute(): float
    {
        return (float) $this->entitled_days
            + (float) $this->carried_forward_days
            + (float) $this->adjustment_days;
    }

    /** What is left once taken and pending days are set aside. */
    public function getRemainingDaysAttribute(): float
    {
        return $this->total_days - (float) $this->taken_days - (float) $this->pending_days;
    }

    /** Excludes pending days — what they have actually used. */
    public function getUsedDaysAttribute(): float
    {
        return (float) $this->taken_days;
    }

    public function getPercentUsedAttribute(): float
    {
        return $this->total_days > 0
            ? round(((float) $this->taken_days / $this->total_days) * 100, 1)
            : 0.0;
    }

    public function covers(float $days): bool
    {
        return $this->remaining_days >= $days;
    }
}
