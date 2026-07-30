<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryImage extends Model
{
    use HasFactory;
    use HasMediaPaths;

    protected $fillable = ['gallery_id', 'path', 'alt_text', 'caption', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (GalleryImage $image) => $image->deleteMedia($image->path));
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->path);
    }
}
