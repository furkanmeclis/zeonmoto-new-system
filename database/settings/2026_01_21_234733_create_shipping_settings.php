<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('shipping.free_shipping_limit_with_pin', 50000.00);
        $this->migrator->add('shipping.free_shipping_limit_without_pin', 50000.00);
        $this->migrator->add('shipping.shipping_cost', 150.00);
        $this->migrator->add('shipping.charge_pin_verified_customers', false);
    }
};
