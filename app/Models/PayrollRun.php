<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One month's payroll in one currency.
 *
 * draft → approved → paid. A draft can be recalculated and edited as often
 * as needed; once approved it is frozen, because by then somebody has
 * signed off the figures and the bank may already have the transfer list.
 */
class PayrollRun extends Model
{
    use LogsActivity;

    public const STATUSES = [
        'draft' => 'Draft',
        'approved' => 'Approved',
        'paid' => 'Paid',
    ];

    protected $fillable = [
        'reference', 'period', 'pay_date', 'currency', 'exchange_rate', 'status',
        'employee_count', 'total_gross', 'total_tax', 'total_social_security', 'total_deductions',
        'total_net', 'total_employer_contributions', 'notes',
        'prepared_by', 'approved_by', 'approved_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'pay_date' => 'date',
            'exchange_rate' => 'decimal:4',
            'employee_count' => 'integer',
            'total_gross' => 'decimal:2',
            'total_tax' => 'decimal:2',
            'total_social_security' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'total_employer_contributions' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['reference', 'status', 'total_gross', 'total_net', 'employee_count', 'approved_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payroll');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeFinalised(Builder $query): Builder
    {
        return $query->whereIn('status', ['approved', 'paid']);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /** Re-add the payslips' figures into the run's totals. */
    public function recalculateTotals(): void
    {
        $sums = $this->payslips()
            ->selectRaw('COUNT(*) as n, SUM(gross_pay) as gross, SUM(income_tax) as tax, SUM(social_security) as ss,
                SUM(total_deductions) as deductions, SUM(net_pay) as net, SUM(employer_social_security) as employer')
            ->first();

        $this->update([
            'employee_count' => (int) $sums->n,
            'total_gross' => (float) $sums->gross,
            'total_tax' => (float) $sums->tax,
            'total_social_security' => (float) $sums->ss,
            'total_deductions' => (float) $sums->deductions,
            'total_net' => (float) $sums->net,
            'total_employer_contributions' => (float) $sums->employer,
        ]);
    }

    /** Gross pay plus what the employer pays on top — the true cost. */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->total_gross + (float) $this->total_employer_contributions;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'approved' => 'info',
            default => 'warning',
        };
    }

    public function getTitleAttribute(): string
    {
        return 'Payroll '.$this->period->format('F Y').' ('.$this->currency.')';
    }
}
