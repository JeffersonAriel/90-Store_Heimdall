<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\FinancialController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\ApiConfigController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\Admin\SecurityController;

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

        // CRUDs de Módulos (Mapeados para os controladores estruturados)
        Route::resource('products', ProductController::class)->names('admin.products');
        Route::post('products/{product}/variations', [ProductController::class, 'addVariation'])->name('admin.products.variations.store');

        Route::resource('suppliers', SupplierController::class)->names('admin.suppliers');
        Route::post('suppliers/{supplier}/evaluate', [SupplierController::class, 'evaluate'])->name('admin.suppliers.evaluate');

        Route::resource('categories', CategoryController::class)->names('admin.categories');
        Route::post('categories/{category}/attributes', [CategoryController::class, 'addAttribute'])->name('admin.categories.attributes.store');

        Route::resource('orders', OrderController::class)->names('admin.orders');
        Route::post('orders/{order}/advance', [OrderController::class, 'advanceStatus'])->name('admin.orders.advance');
        Route::post('orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('admin.orders.confirm-payment');

        Route::get('stock', [StockController::class, 'index'])->name('admin.stock.index');
        Route::post('stock/{id}/adjust', [StockController::class, 'adjust'])->name('admin.stock.adjust');
        Route::get('stock/{id}/history', [StockController::class, 'history'])->name('admin.stock.history');

        Route::get('financial', [FinancialController::class, 'index'])->name('admin.financial.index');
        Route::post('financial/{id}/reconcile', [FinancialController::class, 'reconcile'])->name('admin.financial.reconcile');
        Route::get('financial/reports', [FinancialController::class, 'reports'])->name('admin.financial.reports');
        Route::get('financial/export-bi', [FinancialController::class, 'biExport'])->name('admin.financial.export-bi');

        Route::get('shipping', [ShippingController::class, 'index'])->name('admin.shipping.index');
        Route::put('shipping/{id}', [ShippingController::class, 'update'])->name('admin.shipping.update');

        Route::get('api-config', [ApiConfigController::class, 'index'])->name('admin.api-config.index');
        Route::put('api-config/{slug}', [ApiConfigController::class, 'update'])->name('admin.api-config.update');

        Route::get('import-export', [ImportExportController::class, 'index'])->name('admin.import-export.index');
        Route::get('import-export/template/{tipo}', [ImportExportController::class, 'downloadTemplate'])->name('admin.import-export.template');
        Route::post('import-export/upload', [ImportExportController::class, 'upload'])->name('admin.import-export.upload');
        Route::post('import-export/confirm', [ImportExportController::class, 'confirm'])->name('admin.import-export.confirm');

        // Módulo de Segurança - Restrito ao Perfil de Administrador
        Route::middleware(['admin.only'])->group(function () {
            Route::get('security', [SecurityController::class, 'index'])->name('admin.security.index');
            Route::post('security/block-ip', [SecurityController::class, 'blockIp'])->name('admin.security.block-ip');
            Route::delete('security/unblock-ip/{ip}', [SecurityController::class, 'unblockIp'])->name('admin.security.unblock-ip');
            Route::get('security/export-csv/{tipo}', [SecurityController::class, 'exportCsv'])->name('admin.security.export-csv');
        });

        Route::get('/employees', function () { return 'Funcionários'; })->name('admin.employees.index');
        Route::get('/marketing/coupons', function () { return 'Cupons'; })->name('admin.marketing.coupons');
        Route::get('/marketing/points', function () { return 'Pontos'; })->name('admin.marketing.points');
        Route::get('/marketing/referrals', function () { return 'Indicações'; })->name('admin.marketing.referrals');
    });
});
