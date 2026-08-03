<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A job title. `grade` is a seniority band — lower is more senior — so an
 * ascending sort produces a sensible org chart order.
 */
class Position extends Model
{
    use HasFactory;
    use HasSlug;
    use SoftDeletes;

    protected string $slugSource = 'title';

    protected $fillable = [
        'title', 'slug', 'department_id', 'description', 'grade',
        'min_salary', 'max_salary', 'headcount_budget', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'grade' => 'integer',
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'headcount_budget' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('grade')->orderBy('title');
    }

    public function getGradeLabelAttribute(): string
    {
        return match (true) {
            $this->grade <= 1 => 'Executive',
            $this->grade === 2 => 'Director',
            $this->grade === 3 => 'Manager',
            $this->grade === 4 => 'Supervisor',
            $this->grade === 5 => 'Officer',
            default => 'Support',
        };
    }

    /** True when the position already has as many people as it is budgeted for. */
    public function getIsFullAttribute(): bool
    {
        return $this->headcount_budget !== null
            && $this->employees()->where('is_active', true)->count() >= $this->headcount_budget;
    }
}
