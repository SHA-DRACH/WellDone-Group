<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Something that lapses on a date — a certificate, a permit, a contract.
 *
 * The model names its date column in expiryColumn(); this supplies the
 * scopes the dashboard and reminders use, and a status for badges.
 */
trait HasExpiry
{
    abstract public function expiryColumn(): string;

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull($this->expiryColumn())
            ->whereDate($this->expiryColumn(), '<', today()->toDateString());
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereNotNull($this->expiryColumn())
            ->whereDate($this->expiryColumn(), '>=', today()->toDateString())
            ->whereDate($this->expiryColumn(), '<=', today()->addDays($days)->toDateString());
    }

    /** expired | expiring | valid | none */
    public function getExpiryStateAttribute(): string
    {
        $date = $this->{$this->expiryColumn()};

        return match (true) {
            $date === null => 'none',
            $date->isBefore(today()) => 'expired',
            $date->lte(today()->addDays(60)) => 'expiring',
            default => 'valid',
        };
    }

    public function getExpiryColorAttribute(): string
    {
        return match ($this->expiry_state) {
            'expired' => 'danger',
            'expiring' => 'warning',
            'valid' => 'success',
            default => 'secondary',
        };
    }

    public function getExpiryLabelAttribute(): string
    {
        $date = $this->{$this->expiryColumn()};

        return match ($this->expiry_state) {
            'expired' => 'Expired '.$date->format('j M Y'),
            'expiring' => 'Expires in '.max(0, (int) today()->diffInDays($date)).' days',
            'valid' => 'Valid to '.$date->format('j M Y'),
            default => 'No expiry',
        };
    }
}
