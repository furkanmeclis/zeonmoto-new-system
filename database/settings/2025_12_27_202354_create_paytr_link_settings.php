<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('paytr-link.merchant_id', null);
        $this->migrator->add('paytr-link.merchant_key', null);
        $this->migrator->add('paytr-link.merchant_salt', null);
        $this->migrator->add('paytr-link.debug_on', false);
    }
};

