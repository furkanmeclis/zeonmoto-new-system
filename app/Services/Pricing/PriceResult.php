<?php

namespace App\Services\Pricing;

class PriceResult
{
    public function __construct(
        public float $base,
        public float $final,
        public array $appliedRules = []
    ) {
    }

    /**
     * Get the difference between final and base price.
     */
    public function getDifference(): float
    {
        return $this->final - $this->base;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'base' => $this->base,
            'final' => $this->final,
            'difference' => $this->getDifference(),
            'applied_rules' => $this->appliedRules,
        ];
    }
}

