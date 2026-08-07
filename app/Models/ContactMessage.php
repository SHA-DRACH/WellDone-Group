<?php

namespace App\Models;

use App\Models\Concerns\ScopedToDivision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    use ScopedToDivision;
    use HasFactory;

    public const STATUSES = ['new', 'read', 'replied', 'archived', 'spam'];

    protected $fillable = [
        'user_id', 'division_id', 'name', 'email', 'phone', 'company', 'department', 'subject', 'message',
        'status', 'reply_body', 'replied_by', 'replied_at', 'read_at',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'replied_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    /** The registered account that sent this enquiry, if any. */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The division this enquiry was routed to, if the sender chose one. */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when(
            $status && in_array($status, self::STATUSES, true),
            fn (Builder $q) => $q->where('status', $status)
        );
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $like = '%'.$term.'%';

            $q->where(fn (Builder $inner) => $inner
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('subject', 'like', $like));
        });
    }

    public function markAsRead(): void
    {
        if ($this->status === 'new') {
            $this->forceFill(['status' => 'read', 'read_at' => now()])->save();
        }
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new' => 'primary',
            'read' => 'info',
            'replied' => 'success',
            'spam' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * An enquiry that has not been routed yet belongs to nobody. Showing it
     * to all four divisions would be noise, not access.
     */
    public function unassignedBelongsToGroup(): bool
    {
        return false;
    }
}
