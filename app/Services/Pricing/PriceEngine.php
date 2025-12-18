<?php

namespace App\Services\Pricing;

use App\Models\PriceRule;
use App\Models\Product;
use App\PriceRuleScope;
use App\PriceRuleType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PriceEngine
{
    protected int $cacheTtl = 300; // 5 minutes

    /**
     * Calculate final price for a product.
     */
    public function calculate(Product $product, ?int $dealerId = null): PriceResult
    {
        // Use custom_price if available, otherwise use base_price
        $originalPrice = $product->custom_price !== null && $product->custom_price > 0
            ? (float) $product->custom_price
            : (float) $product->base_price;

        $cacheKey = $this->getCacheKey($product->id, $dealerId, $originalPrice);

        // Try to get from cache
        $cached = $this->getCached($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Original price is 0, return early
        if ($originalPrice === 0.0) {
            $result = new PriceResult($originalPrice, 0.0);
            $this->cache($cacheKey, $result);
            return $result;
        }

        $finalPrice = $originalPrice;
        $appliedRules = [];

        // Get all active rules for this product
        $rules = $this->getActiveRules($product);

        // Apply each rule in priority order
        foreach ($rules as $rule) {
            $priceBefore = $finalPrice;
            $finalPrice = $this->applyRule($finalPrice, $rule);

            // Track applied rule
            $appliedRules[] = [
                'id' => $rule->id,
                'scope' => $rule->scope->value,
                'scope_id' => $rule->scope_id,
                'type' => $rule->type->value,
                'value' => (float) $rule->value,
                'priority' => $rule->priority,
                'price_before' => $priceBefore,
                'price_after' => $finalPrice,
                'difference' => $finalPrice - $priceBefore,
            ];
        }

        $result = new PriceResult($originalPrice, $finalPrice, $appliedRules);
        $this->cache($cacheKey, $result);

        return $result;
    }

    /**
     * Get all active rules for a product, ordered by priority.
     */
    protected function getActiveRules(Product $product): Collection
    {
        $rules = collect();

        // Global rules
        $globalRules = PriceRule::isActive()
            ->forScope(PriceRuleScope::Global)
            ->orderBy('priority')
            ->get();
        $rules = $rules->merge($globalRules);

        // Category rules
        $categoryIds = $product->categories()->pluck('categories.id');
        if ($categoryIds->isNotEmpty()) {
            $categoryRules = PriceRule::isActive()
                ->forScope(PriceRuleScope::Category)
                ->whereIn('scope_id', $categoryIds)
                ->orderBy('priority')
                ->get();
            $rules = $rules->merge($categoryRules);
        }

        // Product rules
        $productRules = PriceRule::isActive()
            ->forScope(PriceRuleScope::Product, $product->id)
            ->orderBy('priority')
            ->get();
        $rules = $rules->merge($productRules);

        // Sort by priority
        return $rules->sortBy('priority')->values();
    }

    /**
     * Apply a single rule to a price.
     */
    protected function applyRule(float $price, PriceRule $rule): float
    {
        if (! $this->isRuleApplicable($rule)) {
            return $price;
        }

        return match ($rule->type) {
            PriceRuleType::Percentage => $price + ($price * (float) $rule->value / 100),
            PriceRuleType::Amount => $price + (float) $rule->value,
        };
    }

    /**
     * Check if a rule is currently applicable.
     */
    protected function isRuleApplicable(PriceRule $rule): bool
    {
        return $rule->isApplicable();
    }

    /**
     * Get cache key for a product and dealer.
     */
    protected function getCacheKey(int $productId, ?int $dealerId = null, ?float $originalPrice = null): string
    {
        $dealerKey = $dealerId ?? 'null';
        $priceKey = $originalPrice !== null ? number_format($originalPrice, 2, '.', '') : 'base';
        return "price:{$productId}:{$dealerKey}:{$priceKey}";
    }

    /**
     * Get cached price result.
     */
    protected function getCached(string $key): ?PriceResult
    {
        $cached = Cache::get($key);
        if ($cached === null) {
            return null;
        }

        // Reconstruct PriceResult from cached array
        $data = is_array($cached) ? $cached : $cached->toArray();
        return new PriceResult(
            $data['base'],
            $data['final'],
            $data['applied_rules'] ?? []
        );
    }

    /**
     * Cache a price result.
     */
    protected function cache(string $key, PriceResult $result): void
    {
        Cache::put($key, $result->toArray(), $this->cacheTtl);
    }

    /**
     * Flush cache for a specific product.
     */
    public function flushForProduct(int $productId): void
    {
        $pattern = "price:{$productId}:*";
        // Laravel cache doesn't support wildcard deletion directly,
        // so we'll delete common patterns
        Cache::forget("price:{$productId}:null");
        // If dealer support is added later, we might need to track keys
    }

    /**
     * Flush all price caches.
     */
    public function flushAll(): void
    {
        // This is a simple implementation
        // In production, you might want to use cache tags if supported
        Cache::flush();
    }
}

