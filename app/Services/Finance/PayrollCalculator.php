<?php

namespace App\Services\Finance;

/**
 * Works out one month's pay for one person.
 *
 * Pure arithmetic: no database, no settings lookups — those are passed in —
 * so it can be tested line by line and gives the same answer every time.
 *
 * Income tax is progressive on annual income. The bands are configured in
 * Finance settings, in their own currency (the Liberia Revenue Code's are in
 * Liberian dollars). Monthly taxable pay is annualised, converted into the
 * band currency at the run's exchange rate, taxed, then brought back to a
 * monthly figure in the pay currency.
 *
 * Social security is a flat percentage of gross pay, for the employee and,
 * separately, for the employer — the employer's share is not deducted from
 * anybody's pay, but it is part of what the payroll costs.
 */
final class PayrollCalculator
{
    /**
     * @param  array<int, array{from: float|int, rate: float|int}>  $taxBands  Annual, ascending.
     * @param  float  $exchangeRate  Band currency per one pay-currency unit; 1 when they are the same.
     */
    public function __construct(
        private readonly array $taxBands,
        private readonly float $exchangeRate,
        private readonly float $employeeSocialSecurityRate,
        private readonly float $employerSocialSecurityRate,
    ) {}

    /**
     * @param  array<int, array{label: string, amount: float|int}>  $allowances
     * @param  array<int, array{label: string, amount: float|int}>  $otherDeductions
     * @return array<string, mixed>
     */
    public function calculate(
        float $basicSalary,
        array $allowances = [],
        float $overtime = 0,
        float $bonus = 0,
        array $otherDeductions = [],
    ): array {
        $allowancesTotal = self::sum($allowances);
        $gross = self::money($basicSalary + $allowancesTotal + $overtime + $bonus);

        $tax = $this->incomeTax($gross);
        $socialSecurity = self::money($gross * $this->employeeSocialSecurityRate / 100);
        $otherTotal = self::sum($otherDeductions);
        $deductions = self::money($tax + $socialSecurity + $otherTotal);

        return [
            'basic_salary' => self::money($basicSalary),
            'allowances' => array_values($allowances),
            'allowances_total' => $allowancesTotal,
            'overtime' => self::money($overtime),
            'bonus' => self::money($bonus),
            'gross_pay' => $gross,
            'income_tax' => $tax,
            'social_security' => $socialSecurity,
            'other_deductions' => array_values($otherDeductions),
            'other_deductions_total' => $otherTotal,
            'total_deductions' => $deductions,
            'net_pay' => self::money($gross - $deductions),
            'employer_social_security' => self::money($gross * $this->employerSocialSecurityRate / 100),
        ];
    }

    /** Monthly income tax on a monthly gross, in the pay currency. */
    public function incomeTax(float $monthlyGross): float
    {
        if ($this->taxBands === [] || $monthlyGross <= 0) {
            return 0.0;
        }

        $annual = $monthlyGross * 12 * $this->exchangeRate;

        return self::money(self::progressive($annual, $this->taxBands) / 12 / $this->exchangeRate);
    }

    /**
     * Tax on an annual amount: each band's rate applies to the slice of
     * income between its "from" and the next band's.
     */
    public static function progressive(float $income, array $bands): float
    {
        usort($bands, fn ($a, $b) => $a['from'] <=> $b['from']);

        $tax = 0.0;

        foreach ($bands as $i => $band) {
            $lower = (float) $band['from'];
            $upper = isset($bands[$i + 1]) ? (float) $bands[$i + 1]['from'] : INF;

            if ($income <= $lower) {
                break;
            }

            $tax += (min($income, $upper) - $lower) * (float) $band['rate'] / 100;
        }

        return $tax;
    }

    /** Monthly equivalent of a salary paid at another frequency. */
    public static function monthly(float $amount, ?string $frequency): float
    {
        return self::money(match ($frequency) {
            'weekly' => $amount * 52 / 12,
            'fortnightly' => $amount * 26 / 12,
            // 22 working days a month is the usual convention for day rates.
            'daily' => $amount * 22,
            default => $amount,
        });
    }

    /** @param array<int, array{amount: float|int|string}> $lines */
    public static function sum(array $lines): float
    {
        return self::money(array_sum(array_map(fn ($line) => (float) ($line['amount'] ?? 0), $lines)));
    }

    public static function money(float $value): float
    {
        return round($value + 0.0, 2);
    }
}
