<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A message from one member of staff to another — typically the CEO or
 * Managing Director writing to a division head — with an optional document.
 * Only the sender and the recipient can open it.
 */
class StaffMessage extends Model
{
    /** File types that may be attached, by extension. */
    public const FILE_TYPES = 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip';

    public const MAX_KB = 20480;

    protected $fillable = [
        'sender_id', 'recipient_id', 'reply_to_id', 'subject', 'body',
        'file_path', 'original_name', 'file_size', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function scopeUnreadFor(Builder $query, User $user): Builder
    {
        return $query->where('recipient_id', $user->id)->whereNull('read_at');
    }

    public function involves(User $user): bool
    {
        return $this->sender_id === $user->id || $this->recipient_id === $user->id;
    }

    public function hasAttachment(): bool
    {
        return filled($this->file_path);
    }

    public function getPreviewableAttribute(): bool
    {
        return $this->hasAttachment()
            && in_array(strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png'], true);
    }
}
