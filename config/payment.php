<?php

return [
    'bank_account' => [
        'name' => env('BANK_ACCOUNT_NAME', ''),
        'bank' => env('BANK_NAME', ''),
        'iban' => env('BANK_IBAN', ''),
        'branch' => env('BANK_BRANCH', ''),
    ],
];

