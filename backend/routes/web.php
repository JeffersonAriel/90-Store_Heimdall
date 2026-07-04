<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;

// ─── ROTA DO INSTALADOR (Auto-desabilita após uso via middleware CheckInstalled) ───
Route::middleware(['installed'])->group(function () {
    Route::get('/install', [InstallController::class, 'index'])->name('install.index');
    Route::post('/install/test', [InstallController::class, 'testConnection'])->name('install.test');
    Route::post('/install/setup', [InstallController::class, 'runSetup'])->name('install.setup');
    Route::post('/install/admin', [InstallController::class, 'createAdmin'])->name('install.admin');
});

// ─── REDIRECIONAMENTOS ───
Route::get('/', function () {
    return redirect('/heimdall');
});

// ─── ROTAS DO HEIMDALL BACK-OFFICE (ADMIN) ───
Route::prefix('heimdall')->group(function () {

    // ─ Auth Funcionários (Visitantes) ─
    Route::middleware(['guest:admin'])->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
        Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
        Route::get('/login/2fa', [AdminAuthController::class, 'show2fa'])->name('admin.login.2fa');
        Route::post('/login/2fa', [AdminAuthController::class, 'verify2fa'])->name('admin.login.2fa.post');
    });

    // ─ Dashboard & Logout (Autenticados) ─
    Route::middleware([
        'installed',
        'admin.auth'
    ])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

        // CRUDs de Módulos (Stubs iniciais para testes de rotas do layout/menu)
        Route::get('/products', function () { return 'Produtos'; })->name('admin.products.index');
        Route::get('/suppliers', function () { return 'Fornecedores'; })->name('admin.suppliers.index');
        Route::get('/categories', function () { return 'Categorias'; })->name('admin.categories.index');
        Route::get('/orders', function () { return 'Pedidos'; })->name('admin.orders.index');
        Route::get('/stock', function () { return 'Estoque'; })->name('admin.stock.index');
        Route::get('/financial', function () { return 'Financeiro'; })->name('admin.financial.index');
        Route::get('/shipping', function () { return 'Frete'; })->name('admin.shipping.index');
        Route::get('/api-config', function () { return 'APIs'; })->name('admin.api-config.index');
        Route::get('/employees', function () { return 'Funcionários'; })->name('admin.employees.index');
        Route::get('/marketing/coupons', function () { return 'Cupons'; })->name('admin.marketing.coupons');
        Route::get('/marketing/points', function () { return 'Pontos'; })->name('admin.marketing.points');
        Route::get('/marketing/referrals', function () { return 'Indicações'; })->name('admin.marketing.referrals');
        Route::get('/import-export', function () { return 'Import/Export'; })->name('admin.import-export.index');
        Route::get('/security', function () { return 'Segurança'; })->name('admin.security.index');
    });
});
