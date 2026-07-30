<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A supporting document attached to a quote request. Stored on the private
 * disk and served through an authorised download route.
 */
class QuoteRequestFile extends Model
{
    use HasFactory;

    protected $fillable = ['quote_request_id', 'path', 'original_name', 'mime_type', 'size'];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleted(fn (QuoteRequestFile $file) => Storage::disk('local')->delete($file->path));
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
