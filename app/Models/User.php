<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * A staff account or registered client.
 *
 * Roles and permissions come from spatie/laravel-permission; the role names
 * are defined in App\Support\Roles.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasMediaPaths;
    use HasRoles;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'position', 'department',
        'division_id', 'avatar', 'bio', 'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'position', 'division_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    // -------------------------------------------------- Relationships

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    public function assignedQuotes(): HasMany
    {
        return $this->hasMany(QuoteRequest::class, 'assigned_to');
    }

    public function reviewedApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'reviewed_by');
    }

    // -------------------------------------------------- Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $like = '%'.$term.'%';

            $q->where(fn (Builder $inner) => $inner
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('position', 'like', $like));
        });
    }

    public function scopeRole_(Builder $query, ?string $role): Builder
    {
        return $query->when($role, fn (Builder $q) => $q->role($role));
    }

    // -------------------------------------------------- Accessors & helpers

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->avatar, asset('images/placeholders/avatar.svg'));
    }

    public function getInitialsAttribute(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    /** Anyone who should be able to reach the admin panel at all. */
    public function canAccessAdmin(): bool
    {
        return $this->is_active && ! $this->hasRole(\App\Support\Roles::CLIENT);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(\App\Support\Roles::SUPER_ADMIN);
    }

    public function recordLogin(?string $ip): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->saveQuietly();
    }
}
