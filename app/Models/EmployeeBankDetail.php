<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Salary and bank details — groundwork for a future Payroll module.
 *
 * Nothing calculates pay from these yet. They live in their own table so that
 * access can be restricted separately from the rest of the employee record:
 * an HR officer may maintain staff details without seeing what anyone earns.
 *
 * Every field here is hidden from array/JSON output.
 */
class EmployeeBankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'basic_salary', 'currency', 'pay_frequency',
        'bank_name', 'bank_branch', 'account_name', 'account_number',
        'tax_identification_number', 'social_security_number',
        'allowances', 'deductions',
    ];

    protected $hidden = [
        'account_number', 'tax_identification_number', 'social_security_number',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'allowances' => 'array',
            'deductions' => 'array',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Last four digits only, for confirming an account without exposing it. */
    public function getMaskedAccountNumberAttribute(): ?string
    {
        if (blank($this->account_number)) {
            return null;
        }

        return str_repeat('•', max(0, strlen($this->account_number) - 4))
            .substr($this->account_number, -4);
    }
}
