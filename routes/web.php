<?php

use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use FurkanMeclis\PayTRLink\Settings\PayTRSettings;
use App\Http\Controllers\HomeController;

// Storage files route - Serve public storage files
Route::get('/storage/{path}', function (string $path) {
    $filePath = storage_path('app/public/' . $path);
    
    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404);
    }
    
    return response()->file($filePath);
})->where('path', '.*')->name('storage');

// Guest route'ları - Inertia.js ile React sayfaları
Route::get('/', [HomeController::class, 'index'])->name('home');

// Shop routes
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/products/{product}', [ShopController::class, 'show'])->name('shop.show');

// Cart routes
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::put('/items/{cartItem}', [CartController::class, 'update'])->name('update');
    Route::delete('/items/{cartItem}', [CartController::class, 'remove'])->name('remove');
    Route::get('/count', [CartController::class, 'count'])->name('count');
});

// Checkout routes
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->name('store');
    Route::get('/success/{order}', [CheckoutController::class, 'success'])->name('success');
});

// Favorites routes
Route::prefix('favorites')->name('favorites.')->group(function () {
    Route::get('/', [FavoritesController::class, 'index'])->name('index');
    Route::post('/products', [FavoritesController::class, 'getProducts'])->name('products');
});

// Price PIN verification routes
Route::prefix('api/price')->name('price.')->group(function () {
    Route::post('/verify-pin', [PriceController::class, 'verifyPin'])->name('verify-pin');
    Route::get('/check-status', [PriceController::class, 'checkPinStatus'])->name('check-status');
    Route::post('/reset-status', [PriceController::class, 'resetPinStatus'])->name('reset-status');
});

// Payment webhook routes
Route::prefix('api/payments')->name('payments.')->group(function () {
    Route::post('/paytr/callback', [PaymentController::class, 'handlePayTRCallback'])->name('paytr.callback');
});

// Order print route
Route::get('/orders/{order}/print', [App\Http\Controllers\OrderPrintController::class, 'print'])->name('orders.print');

Route::get('/paytr/settings',function(){
    $settings = app(PayTRSettings::class);
    $data=[
        "merchant_id" => $settings->getMerchantId(),
        "merchant_key" => $settings->getMerchantKey(),
        "merchant_salt" => $settings->getMerchantSalt(),
        "debug_on" => $settings->getDebugOn(),
    ];
    return response()->json($data);
})->name('paytr.settings');