<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\PriceRule;
use App\Models\Product;
use App\PriceRuleScope;
use App\Services\Pricing\PriceEngine;

class PriceRuleObserver
{
    public function __construct(
        protected PriceEngine $priceEngine
    ) {
    }

    /**
     * Handle the PriceRule "created" event.
     */
    public function created(PriceRule $priceRule): void
    {
        $this->flushCache($priceRule);
    }

    /**
     * Handle the PriceRule "updated" event.
     */
    public function updated(PriceRule $priceRule): void
    {
        $this->flushCache($priceRule);
    }

    /**
     * Handle the PriceRule "deleted" event.
     */
    public function deleted(PriceRule $priceRule): void
    {
        $this->flushCache($priceRule);
    }

    /**
     * Handle the PriceRule "restored" event.
     */
    public function restored(PriceRule $priceRule): void
    {
        $this->flushCache($priceRule);
    }

    /**
     * Handle the PriceRule "force deleted" event.
     */
    public function forceDeleted(PriceRule $priceRule): void
    {
        $this->flushCache($priceRule);
    }

    /**
     * Flush cache based on rule scope.
     */
    protected function flushCache(PriceRule $priceRule): void
    {
        match ($priceRule->scope) {
            PriceRuleScope::Global => $this->priceEngine->flushAll(),
            PriceRuleScope::Category => $this->flushCategoryCache($priceRule->scope_id),
            PriceRuleScope::Product => $this->priceEngine->flushForProduct($priceRule->scope_id),
        };
    }

    /**
     * Flush cache for all products in a category.
     */
    protected function flushCategoryCache(?int $categoryId): void
    {
        if (! $categoryId) {
            return;
        }

        $category = Category::find($categoryId);
        if (! $category) {
            return;
        }

        $productIds = $category->products()->pluck('products.id');
        foreach ($productIds as $productId) {
            $this->priceEngine->flushForProduct($productId);
        }
    }
}
