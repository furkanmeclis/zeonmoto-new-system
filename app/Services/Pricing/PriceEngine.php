<?php

namespace App\Services\Pricing;

use App\Models\Product;
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
        $basePrice = (float) $product->base_price;
        $finalPrice = $product->custom_price !== null && $product->custom_price > 0
            ? (float) $product->custom_price
            : $basePrice;

        $cacheKey = $this->getCacheKey($product->id, $dealerId, $finalPrice);

        // Try to get from cache
        $cached = $this->getCached($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = new PriceResult($basePrice, $finalPrice);
        $this->cache($cacheKey, $result);

        return $result;
    }

    /**
     * Get cache key for a product and dealer.
     */
    protected function getCacheKey(int $productId, ?int $dealerId = null, ?float $price = null): string
    {
        $dealerKey = $dealerId ?? 'null';
        $priceKey = $price !== null ? number_format($price, 2, '.', '') : 'base';
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
            $data['final']
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
        Cache::forget("price:{$productId}:null");
    }

    /**
     * Flush all price caches.
     */
    public function flushAll(): void
    {
        Cache::flush();
    }
}

