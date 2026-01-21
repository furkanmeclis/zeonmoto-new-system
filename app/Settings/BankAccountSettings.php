<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BankAccountSettings extends Settings
{
    public string $name;
    
    public string $bank;
    
    public string $iban;
    
    public string $branch;

    public static function group(): string
    {
        return 'bank_account';
    }
}
