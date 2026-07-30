<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * SEO metadata for either a model (polymorphic) or a named static route.
 */
class Seo extends Model
{
    use HasFactory;
    use HasMediaPaths;

    protected $table = 'seo';

    protected $fillable = [
        'seoable_type', 'seoable_id', 'route_name', 'title', 'description',
        'keywords', 'og_title', 'og_description', 'og_image', 'canonical_url',
        'robots', 'schema_markup',
    ];

    protected function casts(): array
    {
        return ['schema_markup' => 'array'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->og_image);
    }
}
