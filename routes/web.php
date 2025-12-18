<?php

use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\HomeController;

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

// Price PIN verification route
Route::post('/api/price/verify-pin', [PriceController::class, 'verifyPin'])->name('price.verify-pin');

// Order print route
Route::get('/orders/{order}/print', [App\Http\Controllers\OrderPrintController::class, 'print'])->name('orders.print');
