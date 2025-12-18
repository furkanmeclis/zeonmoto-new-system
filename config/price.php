<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Price PIN
    |--------------------------------------------------------------------------
    |
    | 4 haneli PIN kodu ile fiyat görünürlüğü kontrolü için kullanılır.
    | .env dosyasında PRICE_PIN değişkeni olarak tanımlanmalıdır.
    |
    */

    'pin' => env('PRICE_PIN', null),
];

