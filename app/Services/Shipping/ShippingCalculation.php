<?php

namespace App\Services\Shipping;

readonly class ShippingCalculation
{
    public function __construct(
        public float $shippingCost,
        public bool $isFree,
        public float $remainingAmount
    ) {
    }
}
