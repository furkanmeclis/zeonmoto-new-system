<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('bank_account.name', '');
        $this->migrator->add('bank_account.bank', '');
        $this->migrator->add('bank_account.iban', '');
        $this->migrator->add('bank_account.branch', '');
    }
};
