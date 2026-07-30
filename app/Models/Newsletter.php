<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Newsletter subscriber. Double opt-in — only rows with `confirmed_at` set
 * and `unsubscribed_at` null are mailable.
 */
class Newsletter extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'name', 'token', 'confirmed_at', 'unsubscribed_at',
        'source', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected $hidden = ['token', 'ip_address'];

    protected static function booted(): void
    {
        static::creating(function (Newsletter $subscriber): void {
            $subscriber->token ??= Str::random(64);
        });
    }

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function getIsSubscribedAttribute(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }

    public function confirm(): void
    {
        $this->forceFill([
            'confirmed_at' => $this->confirmed_at ?? now(),
            'unsubscribed_at' => null,
        ])->save();
    }

    public function unsubscribe(): void
    {
        $this->forceFill(['unsubscribed_at' => now()])->save();
    }
}
