<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One employee's pay for one run. The name, position and bank account are
 * copied in when it is produced, so the payslip reads the same in five
 * years whatever has changed on the employee record since.
 */
class Payslip extends Model
{
    protected $fillable = [
        'payroll_run_id', 'employee_id', 'employee_number', 'employee_name', 'department', 'position',
        'bank_name', 'account_number', 'basic_salary', 'allowances', 'allowances_total', 'overtime', 'bonus',
        'gross_pay', 'income_tax', 'social_security', 'other_deductions', 'other_deductions_total',
        'total_deductions', 'net_pay', 'employer_social_security', 'notes',
    ];

    protected $hidden = ['account_number'];

    protected function casts(): array
    {
        return [
            'allowances' => 'array',
            'other_deductions' => 'array',
            'basic_salary' => 'decimal:2',
            'allowances_total' => 'decimal:2',
            'overtime' => 'decimal:2',
            'bonus' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'income_tax' => 'decimal:2',
            'social_security' => 'decimal:2',
            'other_deductions_total' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
            'employer_social_security' => 'decimal:2',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    /** The account number with all but the last four digits hidden. */
    public function getMaskedAccountAttribute(): ?string
    {
        if (blank($this->account_number)) {
            return null;
        }

        $digits = (string) $this->account_number;

        return str_repeat('•', max(0, mb_strlen($digits) - 4)).mb_substr($digits, -4);
    }
}
