<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;
    use HasSlug;

    protected $fillable = ['name', 'slug'];

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'blog_tag');
    }

    public function getUrlAttribute(): string
    {
        return route('site.blog.index', ['tag' => $this->slug]);
    }
}
