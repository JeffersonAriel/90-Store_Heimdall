<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes — HEIMDALL ERP + 90+ STORE
|--------------------------------------------------------------------------
|
| Instalador → se não instalado, redireciona para /install
| ERP         → rotas autenticadas em /erp/*
| Store       → rotas públicas em /
|
*/

// ─── Instalador Web ───────────────────────────────────────────────────────────
Route::prefix('install')->group(function () {
    $installed = storage_path('app/installed.lock');

    Route::get('/', function () use ($installed) {
        if (File::exists($installed)) {
            return redirect('/');
        }
        return Inertia::render('Installer/Index');
    })->name('installer.index');

    Route::post('/check', [\App\Http\Controllers\InstallerController::class, 'checkRequirements'])
        ->name('installer.check');

    Route::post('/database', [\App\Http\Controllers\InstallerController::class, 'testDatabase'])
        ->name('installer.database');

    Route::post('/setup', [\App\Http\Controllers\InstallerController::class, 'setup'])
        ->name('installer.setup');
});

// ─── Redireciona para installer se não instalado ──────────────────────────────
Route::middleware(\App\Http\Middleware\EnsureInstalled::class)->group(function () {

    // ─── ERP (área administrativa) ────────────────────────────────────────────
    Route::prefix('erp')->name('erp.')->group(function () {
        // Auth
        Route::middleware('guest')->group(function () {
            Route::get('/login', fn() => Inertia::render('ERP/Auth/Login'))->name('login');
            Route::post('/login', [\App\Http\Controllers\ERP\AuthController::class, 'login'])->name('login.post');
        });

        // 2FA
        Route::middleware(['auth', \App\Http\Middleware\TwoFactorMiddleware::class])->group(function () {
            Route::get('/2fa', fn() => Inertia::render('ERP/Auth/TwoFactor'))->name('2fa');
            Route::post('/2fa', [\App\Http\Controllers\ERP\AuthController::class, 'verifyTwoFactor'])->name('2fa.verify');
        });

        Route::get('/logout', [\App\Http\Controllers\ERP\AuthController::class, 'logout'])->name('logout');

        // Dashboard e módulos ERP (autenticados)
        Route::middleware(['auth', '2fa.verified', 'permission'])->group(function () {
            Route::get('/dashboard', fn() => Inertia::render('ERP/Dashboard'))->name('dashboard');

            // Produtos
            Route::get('/products', fn() => Inertia::render('ERP/Products/Index'))->name('products.index');
            Route::get('/products/create', fn() => Inertia::render('ERP/Products/Create'))->name('products.create');
            Route::get('/products/{id}', fn() => Inertia::render('ERP/Products/Show'))->name('products.show');

            // Categorias / Marcas
            Route::get('/categories', fn() => Inertia::render('ERP/Categories/Index'))->name('categories.index');
            Route::get('/brands', fn() => Inertia::render('ERP/Brands/Index'))->name('brands.index');

            // Clientes
            Route::get('/customers', fn() => Inertia::render('ERP/Customers/Index'))->name('customers.index');

            // Pedidos
            Route::get('/orders', fn() => Inertia::render('ERP/Orders/Index'))->name('orders.index');
            Route::get('/orders/{id}', fn() => Inertia::render('ERP/Orders/Show'))->name('orders.show');

            // Estoque
            Route::get('/stock', fn() => Inertia::render('ERP/Stock/Index'))->name('stock.index');

            // Compras
            Route::get('/purchases', fn() => Inertia::render('ERP/Purchases/Index'))->name('purchases.index');

            // Financeiro
            Route::get('/financial', fn() => Inertia::render('ERP/Financial/Index'))->name('financial.index');

            // CRM
            Route::get('/crm', fn() => Inertia::render('ERP/CRM/Index'))->name('crm.index');

            // Marketing
            Route::get('/marketing', fn() => Inertia::render('ERP/Marketing/Index'))->name('marketing.index');

            // Relatórios
            Route::get('/reports', fn() => Inertia::render('ERP/Reports/Index'))->name('reports.index');

            // BI
            Route::get('/bi', fn() => Inertia::render('ERP/BI/Index'))->name('bi.index');

            // Usuários & Permissões
            Route::get('/users', fn() => Inertia::render('ERP/Users/Index'))->name('users.index');
            Route::get('/permissions', fn() => Inertia::render('ERP/Permissions/Index'))->name('permissions.index');

            // Auditoria
            Route::get('/audit', fn() => Inertia::render('ERP/Audit/Index'))->name('audit.index');

            // Configurações
            Route::get('/settings', fn() => Inertia::render('ERP/Settings/Index'))->name('settings.index');

            // IA
            Route::get('/ai', fn() => Inertia::render('ERP/AI/Index'))->name('ai.index');
        });
    });

    // ─── 90+ STORE (e-commerce público) ──────────────────────────────────────
    Route::name('store.')->group(function () {
        Route::get('/', fn() => Inertia::render('Store/Home'))->name('home');
        Route::get('/produtos', fn() => Inertia::render('Store/Products/List'))->name('products');
        Route::get('/produtos/{slug}', fn() => Inertia::render('Store/Products/Show'))->name('product');
        Route::get('/categoria/{slug}', fn() => Inertia::render('Store/Category'))->name('category');
        Route::get('/marca/{slug}', fn() => Inertia::render('Store/Brand'))->name('brand');
        Route::get('/busca', fn() => Inertia::render('Store/Search'))->name('search');
        Route::get('/carrinho', fn() => Inertia::render('Store/Cart'))->name('cart');
        Route::get('/checkout', fn() => Inertia::render('Store/Checkout'))->name('checkout');
        Route::get('/favoritos', fn() => Inertia::render('Store/Wishlist'))->name('wishlist');
        Route::get('/blog', fn() => Inertia::render('Store/Blog/Index'))->name('blog');
        Route::get('/blog/{slug}', fn() => Inertia::render('Store/Blog/Show'))->name('blog.show');
        Route::get('/sobre', fn() => Inertia::render('Store/Pages/About'))->name('about');
        Route::get('/contato', fn() => Inertia::render('Store/Pages/Contact'))->name('contact');
        Route::get('/rastreamento', fn() => Inertia::render('Store/Tracking'))->name('tracking');

        // Minha Conta
        Route::prefix('minha-conta')->name('account.')->middleware('auth')->group(function () {
            Route::get('/', fn() => Inertia::render('Store/Account/Index'))->name('index');
            Route::get('/pedidos', fn() => Inertia::render('Store/Account/Orders'))->name('orders');
            Route::get('/pedidos/{id}', fn() => Inertia::render('Store/Account/OrderShow'))->name('order');
            Route::get('/enderecos', fn() => Inertia::render('Store/Account/Addresses'))->name('addresses');
            Route::get('/cashback', fn() => Inertia::render('Store/Account/Cashback'))->name('cashback');
        });

        // Auth store (clientes)
        Route::prefix('conta')->middleware('guest')->group(function () {
            Route::get('/entrar', fn() => Inertia::render('Store/Auth/Login'))->name('login');
            Route::get('/cadastro', fn() => Inertia::render('Store/Auth/Register'))->name('register');
            Route::get('/recuperar-senha', fn() => Inertia::render('Store/Auth/ForgotPassword'))->name('forgot-password');
        });
    });
});
