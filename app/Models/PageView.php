<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A single first-party page view. Written asynchronously by the
 * TrackPageView middleware; read by the dashboard traffic widget.
 */
class PageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'path', 'route_name', 'referrer', 'visitor_hash', 'device', 'country', 'viewed_at',
    ];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public function scopeSince(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->where('viewed_at', '>=', $date);
    }

    /** Distinct visitors (by hashed IP + UA) rather than raw hits. */
    public function scopeUniqueVisitors(Builder $query): Builder
    {
        return $query->distinct('visitor_hash');
    }
}
