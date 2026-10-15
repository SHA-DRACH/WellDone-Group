<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** Money received against an invoice. This, not the invoice, is income. */
class InvoicePayment extends Model
{
    use LogsActivity;

    protected $fillable = ['invoice_id', 'paid_on', 'amount', 'method', 'reference', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['invoice_id', 'paid_on', 'amount', 'method', 'reference'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('invoice-payment');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getMethodLabelAttribute(): ?string
    {
        return $this->method ? (Expense::PAYMENT_METHODS[$this->method] ?? $this->method) : null;
    }
}
