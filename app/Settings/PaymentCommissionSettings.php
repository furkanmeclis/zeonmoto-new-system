<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaymentCommissionSettings extends Settings
{
    public float $commission_rate;

    public static function group(): string
    {
        return 'payment-commission';
    }
}

