<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\StoreSettingsController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CheckoutController;

/*
|--------------------------------------------------------------------------
| API Routes — 90-Store Customer Front-end
|--------------------------------------------------------------------------
*/

// ─── ENDPOINTS PÚBLICOS DA VITRINE E LOJA ───
Route::get('/store-settings', [StoreSettingsController::class, 'index']);
Route::get('/catalog', [StoreApiController::class, 'getCatalog']);
Route::get('/products/{slug}', [StoreApiController::class, 'getProductDetail']);
Route::get('/cep/{cep}', [StoreApiController::class, 'lookupCep']);
Route::post('/shipping/quote', [StoreApiController::class, 'quoteShipping']);

// ─── LOGIN / REGISTRO CLIENTE ───
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/login', [CustomerAuthController::class, 'login']);

// ─── ROTAS PROTEGIDAS VIA SANCTUM ───
Route::middleware('auth:sanctum')->group(function () {
    // Perfil
    Route::get('/profile', [CustomerAuthController::class, 'profile']);
    Route::post('/logout', [CustomerAuthController::class, 'logout']);

    // Endereços múltiplos do cliente
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    // Checkout de compras
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
});
