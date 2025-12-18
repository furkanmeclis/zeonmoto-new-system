<?php

namespace App\Models;

use App\PriceRuleScope;
use App\PriceRuleType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceRule extends Model
{
    protected $fillable = [
        'scope',
        'scope_id',
        'type',
        'value',
        'priority',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'scope' => PriceRuleScope::class,
        'type' => PriceRuleType::class,
        'value' => 'decimal:2',
        'priority' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the category if scope is category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'scope_id')
            ->where('scope', PriceRuleScope::Category->value);
    }

    /**
     * Get the product if scope is product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'scope_id')
            ->where('scope', PriceRuleScope::Product->value);
    }

    /**
     * Scope a query to only include active rules within date range.
     */
    public function scopeIsActive(Builder $query): Builder
    {
        $now = now();

        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    /**
     * Scope a query to filter by scope and scope_id.
     */
    public function scopeForScope(Builder $query, PriceRuleScope $scope, ?int $scopeId = null): Builder
    {
        $query->where('scope', $scope->value);

        if ($scopeId !== null) {
            $query->where('scope_id', $scopeId);
        } else {
            $query->whereNull('scope_id');
        }

        return $query;
    }

    /**
     * Check if rule is currently applicable.
     */
    public function isApplicable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
