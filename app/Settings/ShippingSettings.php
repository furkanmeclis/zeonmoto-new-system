<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ShippingSettings extends Settings
{
    public float $free_shipping_limit_with_pin;
    
    public float $free_shipping_limit_without_pin;
    
    public float $shipping_cost;
    
    public bool $charge_pin_verified_customers;

    public static function group(): string
    {
        return 'shipping';
    }
}
