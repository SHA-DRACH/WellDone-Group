<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;
    use HasMediaPaths;

    protected $fillable = [
        'title', 'issuer', 'reference_number', 'description', 'image',
        'file_path', 'issued_at', 'expires_at', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('issued_at');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->image);
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->file_path);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
