<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id', 'parent_id', 'author_name', 'author_email',
        'author_website', 'body', 'is_approved', 'ip_address',
    ];

    protected function casts(): array
    {
        return ['is_approved' => 'boolean'];
    }

    protected $hidden = ['author_email', 'ip_address'];

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_approved', true)->oldest();
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }
}
