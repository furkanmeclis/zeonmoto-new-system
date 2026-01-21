<?php

namespace App\Services\Shipping;

use App\Settings\ShippingSettings;

class ShippingService
{
    public function __construct(
        protected ShippingSettings $shippingSettings
    ) {
    }

    /**
     * Calculate shipping cost based on subtotal and PIN verification status.
     *
     * @param float $subtotal
     * @param bool $isPinVerified
     * @return ShippingCalculation
     */
    public function calculateShippingCost(float $subtotal, bool $isPinVerified): ShippingCalculation
    {
        // Determine the free shipping limit based on PIN status
        $freeShippingLimit = $isPinVerified
            ? $this->shippingSettings->free_shipping_limit_with_pin
            : $this->shippingSettings->free_shipping_limit_without_pin;

        // Check if subtotal meets the free shipping threshold
        if ($subtotal >= $freeShippingLimit) {
            return new ShippingCalculation(
                shippingCost: 0.0,
                isFree: true,
                remainingAmount: 0.0
            );
        }

        // If subtotal is below the limit, check if we should charge
        // PIN verified customers might be exempt if charge_pin_verified_customers is false
        if ($isPinVerified && !$this->shippingSettings->charge_pin_verified_customers) {
            return new ShippingCalculation(
                shippingCost: 0.0,
                isFree: true,
                remainingAmount: $this->getRemainingAmountForFreeShipping($subtotal, $isPinVerified)
            );
        }

        // Apply shipping cost
        $shippingCost = $this->shippingSettings->shipping_cost;
        $remainingAmount = $this->getRemainingAmountForFreeShipping($subtotal, $isPinVerified);

        return new ShippingCalculation(
            shippingCost: $shippingCost,
            isFree: false,
            remainingAmount: $remainingAmount
        );
    }

    /**
     * Get remaining amount needed for free shipping.
     *
     * @param float $subtotal
     * @param bool $isPinVerified
     * @return float
     */
    public function getRemainingAmountForFreeShipping(float $subtotal, bool $isPinVerified): float
    {
        $freeShippingLimit = $isPinVerified
            ? $this->shippingSettings->free_shipping_limit_with_pin
            : $this->shippingSettings->free_shipping_limit_without_pin;

        $remaining = $freeShippingLimit - $subtotal;
        
        return max(0.0, $remaining);
    }
}
