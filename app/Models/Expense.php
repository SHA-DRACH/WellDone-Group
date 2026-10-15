<?php

namespace App\Models;

use App\Models\Concerns\ScopedToDivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Money the company has spent, or been asked to spend.
 *
 * pending → approved → paid, or pending → rejected. Only approved and paid
 * expenses count against budgets and in the profit & loss: a pending claim
 * is a request, not yet a cost.
 */
class Expense extends Model
{
    use LogsActivity;
    use ScopedToDivision;
    use SoftDeletes;

    public const STATUSES = [
        'pending' => 'Awaiting approval',
        'approved' => 'Approved',
        'paid' => 'Paid',
        'rejected' => 'Rejected',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'mobile_money' => 'Mobile money',
        'card' => 'Card',
    ];

    /** Statuses that make an expense a real cost. */
    public const COUNTED = ['approved', 'paid'];

    protected $fillable = [
        'reference', 'expense_category_id', 'division_id', 'department_id', 'expense_date', 'payee',
        'description', 'amount', 'currency', 'payment_method', 'receipt_path', 'status',
        'submitted_by', 'approved_by', 'approved_at', 'rejection_reason', 'paid_on', 'notes',
    ];

    protected $hidden = ['receipt_path'];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'paid_on' => 'date',
            'approved_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Expense $expense): void {
            $expense->reference ??= static::nextReference($expense->expense_date?->year ?? now()->year);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reference', 'amount', 'status', 'payee', 'expense_category_id', 'division_id', 'approved_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('expense');
    }

    /** EXP-2026-00001; never reuses a number, deleted rows included. */
    public static function nextReference(int $year): string
    {
        $prefix = 'EXP-'.$year.'-';

        $last = static::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(reference) DESC, reference DESC')
            ->value('reference');

        return $prefix.str_pad((string) ($last ? (int) Str::afterLast($last, '-') + 1 : 1), 5, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------- Relationships

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // -------------------------------------------------- Scopes

    public function scopeCounted(Builder $query): Builder
    {
        return $query->whereIn('status', self::COUNTED);
    }

    public function scopeBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('expense_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('expense_date', '<=', $to));
    }

    // -------------------------------------------------- Accessors

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'approved' => 'info',
            'rejected' => 'danger',
            default => 'warning',
        };
    }

    public function getPaymentMethodLabelAttribute(): ?string
    {
        return $this->payment_method ? (self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method) : null;
    }

    /**
     * An expense with no division is a head-office cost, and is the group's
     * to see — not every division's.
     */
    public function unassignedBelongsToGroup(): bool
    {
        return false;
    }
}
