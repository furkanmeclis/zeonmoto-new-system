<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('paytr-link.merchant_id', '');
        $this->migrator->add('paytr-link.merchant_key', '');
        $this->migrator->add('paytr-link.merchant_salt', '');
        $this->migrator->add('paytr-link.debug_on', false);
    }
};
