<?php

namespace App\Models;

use App\Models\Concerns\ScopedToDivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A bill to a client.
 *
 * The totals are stored, not worked out on every read, so an invoice that
 * has been sent reads the same for ever — but they are only ever written by
 * recalculate(), from the line items and payments, never typed in.
 *
 * "Overdue" is not a stored status. It is a sent invoice past its due date,
 * which changes at midnight without anybody doing anything; storing it would
 * need a job to flip it, and would be wrong whenever that job had not run.
 */
class Invoice extends Model
{
    use LogsActivity;
    use ScopedToDivision;
    use SoftDeletes;

    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'partially_paid' => 'Part-paid',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ];

    /** Statuses where money is still owed. */
    public const OPEN = ['sent', 'partially_paid'];

    protected $fillable = [
        'number', 'client_id', 'division_id', 'quote_request_id', 'project_id',
        'bill_to_name', 'bill_to_email', 'bill_to_phone', 'bill_to_address',
        'issue_date', 'due_date', 'currency', 'subtotal', 'discount', 'tax_rate', 'tax_amount',
        'total', 'amount_paid', 'status', 'notes', 'terms', 'sent_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            $invoice->number ??= static::nextNumber($invoice->issue_date?->year ?? now()->year);
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['number', 'bill_to_name', 'total', 'amount_paid', 'status', 'due_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('invoice');
    }

    /** INV-2026-0001. */
    public static function nextNumber(int $year): string
    {
        $prefix = 'INV-'.$year.'-';

        $last = static::withTrashed()
            ->where('number', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(number) DESC, number DESC')
            ->value('number');

        return $prefix.str_pad((string) ($last ? (int) Str::afterLast($last, '-') + 1 : 1), 4, '0', STR_PAD_LEFT);
    }

    // -------------------------------------------------- Relationships

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('paid_on')->orderBy('id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -------------------------------------------------- Scopes

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->outstanding()->whereDate('due_date', '<', today()->toDateString());
    }

    // -------------------------------------------------- Money

    /**
     * Work the totals out again from the lines and the payments, and move
     * the status along to match: a sent invoice that is now fully paid
     * becomes paid; one that has had a payment reversed goes back.
     */
    public function recalculate(): void
    {
        $subtotal = round((float) $this->items()->sum('amount'), 2);
        $discount = min(round((float) $this->discount, 2), $subtotal);
        $tax = round(($subtotal - $discount) * (float) $this->tax_rate / 100, 2);
        $total = round($subtotal - $discount + $tax, 2);
        $paid = round((float) $this->payments()->sum('amount'), 2);

        $status = $this->status;

        if (! in_array($status, ['draft', 'cancelled'], true)) {
            $status = match (true) {
                $paid >= $total && $total > 0 => 'paid',
                $paid > 0 => 'partially_paid',
                default => 'sent',
            };
        }

        $this->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax_amount' => $tax,
            'total' => $total,
            'amount_paid' => $paid,
            'status' => $status,
        ]);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, self::OPEN, true) && $this->due_date->isBefore(today());
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent'], true) && (float) $this->amount_paid === 0.0;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->isOverdue() ? 'Overdue' : (self::STATUSES[$this->status] ?? ucfirst($this->status));
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->isOverdue()) {
            return 'danger';
        }

        return match ($this->status) {
            'paid' => 'success',
            'partially_paid' => 'info',
            'sent' => 'primary',
            'cancelled' => 'secondary',
            default => 'warning',
        };
    }

    public function getDaysOverdueAttribute(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(today()) : 0;
    }

    /** A head-office invoice is the group's, not every division's. */
    public function unassignedBelongsToGroup(): bool
    {
        return false;
    }
}
