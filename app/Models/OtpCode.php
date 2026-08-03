<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    public const PURPOSES = ['email_verification', 'password_reset', 'password_change'];

    protected $fillable = [
        'user_id', 'email', 'purpose', 'code_hash',
        'attempts', 'expires_at', 'consumed_at', 'ip_address',
    ];

    /** The hash is never exposed, even accidentally. */
    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Still usable: not spent, not expired, not locked out. */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', \App\Services\OtpService::MAX_ATTEMPTS);
    }

    public function scopeForPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    public function getIsLockedAttribute(): bool
    {
        return $this->attempts >= \App\Services\OtpService::MAX_ATTEMPTS;
    }
}
