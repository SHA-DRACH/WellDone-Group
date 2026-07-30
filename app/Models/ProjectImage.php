<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectImage extends Model
{
    use HasFactory;
    use HasMediaPaths;

    protected $fillable = ['project_id', 'path', 'alt_text', 'caption', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        // Keep the disk in step with the database.
        static::deleted(fn (ProjectImage $image) => $image->deleteMedia($image->path));
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->path);
    }
}
