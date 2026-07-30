<?php

namespace App\Models;

use App\Models\Concerns\HasMediaPaths;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * News & blog article.
 */
class Blog extends Model
{
    use HasFactory;
    use HasMediaPaths;
    use HasSlug;
    use LogsActivity;
    use SoftDeletes;

    protected string $slugSource = 'title';

    public const STATUSES = ['draft', 'published', 'archived'];

    protected $fillable = [
        'blog_category_id', 'user_id', 'title', 'slug', 'excerpt', 'body',
        'featured_image', 'featured_image_alt', 'status', 'published_at',
        'reading_time', 'is_featured', 'allow_comments',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'reading_time' => 'integer',
            'views' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Blog $blog): void {
            // Publishing without an explicit date means "now".
            if ($blog->status === 'published' && blank($blog->published_at)) {
                $blog->published_at = now();
            }

            $blog->reading_time = $blog->estimateReadingTime();

            if (blank($blog->excerpt) && filled($blog->body)) {
                $blog->excerpt = Str::limit(strip_tags($blog->body), 180);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'status', 'published_at', 'blog_category_id', 'is_featured'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('blog');
    }

    // -------------------------------------------------- Relationships

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'blog_tag');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->where('is_approved', true)->whereNull('parent_id')->latest();
    }

    public function seo(): MorphOne
    {
        return $this->morphOne(Seo::class, 'seoable');
    }

    // -------------------------------------------------- Scopes

    /** Visible to the public: published and not scheduled for the future. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInCategory(Builder $query, ?string $slug): Builder
    {
        return $query->when($slug, fn (Builder $q) => $q->whereHas(
            'category',
            fn (Builder $c) => $c->where('slug', $slug)
        ));
    }

    public function scopeWithTag(Builder $query, ?string $slug): Builder
    {
        return $query->when($slug, fn (Builder $q) => $q->whereHas(
            'tags',
            fn (Builder $t) => $t->where('slug', $slug)
        ));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $like = '%'.$term.'%';

            $q->where(fn (Builder $inner) => $inner
                ->where('title', 'like', $like)
                ->orWhere('excerpt', 'like', $like)
                ->orWhere('body', 'like', $like));
        });
    }

    // -------------------------------------------------- Accessors & helpers

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->mediaUrl($this->featured_image, asset('images/placeholders/article.svg'));
    }

    public function getUrlAttribute(): string
    {
        return route('site.blog.show', $this->slug);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    protected function estimateReadingTime(): int
    {
        $words = str_word_count(strip_tags((string) $this->body));

        return max(1, (int) ceil($words / 220));
    }

    public function recordView(): void
    {
        $this->newQuery()->whereKey($this->getKey())->increment('views');
    }
}
