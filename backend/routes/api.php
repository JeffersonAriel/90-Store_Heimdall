<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\StoreSettingsController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\CustomerOrderController;

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

// ─── ROTAS DO WEBHOOK E CONSULTAS PÚBLICAS/SEGURAS ───
Route::post('/payments/infinitepay/webhook', [\App\Http\Controllers\Api\InfinitePayController::class, 'webhook']);
Route::get('/orders/public/{id}', [\App\Http\Controllers\Api\CustomerOrderController::class, 'showPublic']);

// ─── ROTAS PROTEGIDAS VIA SANCTUM ───
Route::middleware('auth:sanctum')->group(function () {
    // Perfil
    Route::get('/profile',  [CustomerAuthController::class, 'profile']);
    Route::put('/profile',  [CustomerAuthController::class, 'updateProfile']);
    Route::post('/logout',  [CustomerAuthController::class, 'logout']);

    // Histórico de Pontos e Cupons do Cliente
    Route::get('/points/history', function (\Illuminate\Http\Request $request) {
        $historico = \App\Models\PontoFidelidade::where('cliente_id', $request->user()->id)
            ->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'historico' => $historico]);
    });
    Route::get('/coupons/mine', function (\Illuminate\Http\Request $request) {
        // Retorna cupons ativos ou disponíveis
        $cupons = \App\Models\Cupom::where('ativo', true)
            ->where(function($q) {
                $q->whereNull('data_expiracao')->orWhere('data_expiracao', '>=', now());
            })->get();
        return response()->json(['success' => true, 'cupons' => $cupons]);
    });

    // Endereços múltiplos do cliente
    Route::get('/addresses',                    [AddressController::class, 'index']);
    Route::post('/addresses',                   [AddressController::class, 'store']);
    Route::put('/addresses/{id}',               [AddressController::class, 'update']);
    Route::patch('/addresses/{id}/principal',   [AddressController::class, 'setPrincipal']);
    Route::delete('/addresses/{id}',            [AddressController::class, 'destroy']);

    // Pedidos
    Route::get('/orders/pix-key', [CustomerOrderController::class, 'pixKey']);
    Route::get('/orders', [CustomerOrderController::class, 'index']);
    Route::get('/orders/{id}', [CustomerOrderController::class, 'show']);

    // Checkout de compras
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
});

