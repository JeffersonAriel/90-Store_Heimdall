<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\StoreSettingsAdminController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\FinancialController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\ApiConfigController;
use App\Http\Controllers\Admin\ImportExportController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\InsumoController;
use App\Http\Controllers\Admin\AgendaController;

// ─── CRM Enterprise Controllers ───
use App\Http\Controllers\Admin\Crm\CrmDashboardController;
use App\Http\Controllers\Admin\Crm\CrmLeadController;
use App\Http\Controllers\Admin\Crm\CrmPipelineController;
use App\Http\Controllers\Admin\Crm\CrmClienteController;
use App\Http\Controllers\Admin\Crm\CrmTarefaController;
use App\Http\Controllers\Admin\Crm\CrmTemplateController;
use App\Http\Controllers\Admin\Crm\CrmCampanhaController;
use App\Http\Controllers\Admin\Crm\CrmAutomacaoController;
use App\Http\Controllers\Admin\Crm\CrmSegmentoController;

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
        Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1')->name('admin.login.post');
        Route::get('/login/2fa', [AdminAuthController::class, 'show2fa'])->name('admin.login.2fa');
        Route::post('/login/2fa', [AdminAuthController::class, 'verify2fa'])->middleware('throttle:5,1')->name('admin.login.2fa.post');
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

        Route::post('insumos', [InsumoController::class, 'store'])->name('admin.insumos.store');
        Route::put('insumos/{id}', [InsumoController::class, 'update'])->name('admin.insumos.update');
        Route::delete('insumos/{id}', [InsumoController::class, 'destroy'])->name('admin.insumos.destroy');

        Route::resource('suppliers', SupplierController::class)->names('admin.suppliers');
        Route::post('suppliers/{supplier}/evaluate', [SupplierController::class, 'evaluate'])->name('admin.suppliers.evaluate');

        Route::resource('categories', CategoryController::class)->names('admin.categories');
        Route::post('categories/{category}/attributes', [CategoryController::class, 'addAttribute'])->name('admin.categories.attributes.store');

        Route::resource('orders', OrderController::class)->names('admin.orders');
        Route::post('orders/{order}/advance', [OrderController::class, 'advanceStatus'])->name('admin.orders.advance');
        Route::post('orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment'])->name('admin.orders.confirm-payment');
        Route::patch('orders/{order}/update-frete', [OrderController::class, 'updateFrete'])->name('admin.orders.update-frete');
        Route::post('orders/{order}/generate-label', [OrderController::class, 'generateLabel'])->name('admin.orders.generate-label');
        Route::get('orders/{order}/print-label', [OrderController::class, 'printLabel'])->name('admin.orders.print-label');
        Route::post('orders/{order}/sync-tracking', [OrderController::class, 'syncSuperFreteTracking'])->name('admin.orders.sync-tracking');
        Route::post('orders/{order}/update-item-costs', [OrderController::class, 'updateItemCosts'])->name('admin.orders.update-item-costs');

        Route::resource('agenda', AgendaController::class)->names('admin.agenda');

        Route::get('stock', [StockController::class, 'index'])->name('admin.stock.index');
        Route::post('stock/{id}/adjust', [StockController::class, 'adjust'])->name('admin.stock.adjust');
        Route::get('stock/{id}/history', [StockController::class, 'history'])->name('admin.stock.history');

        Route::get('financial', [FinancialController::class, 'index'])->name('admin.financial.index');
        Route::post('financial', [FinancialController::class, 'store'])->name('admin.financial.store');
        Route::put('financial/{id}', [FinancialController::class, 'update'])->name('admin.financial.update');
        Route::delete('financial/{id}', [FinancialController::class, 'destroy'])->name('admin.financial.destroy');
        Route::post('financial/{id}/reconcile', [FinancialController::class, 'reconcile'])->name('admin.financial.reconcile');
        Route::get('financial/reports', [FinancialController::class, 'reports'])->name('admin.financial.reports');
        Route::get('financial/export-bi', [FinancialController::class, 'biExport'])->name('admin.financial.export-bi');

        Route::get('shipping', [ShippingController::class, 'index'])->name('admin.shipping.index');
        Route::put('shipping/{id}', [ShippingController::class, 'update'])->name('admin.shipping.update');

        Route::get('api-config', [ApiConfigController::class, 'index'])->name('admin.api-config.index');
        Route::post('api-config', [ApiConfigController::class, 'store'])->name('admin.api-config.store');
        Route::put('api-config/{slug}', [ApiConfigController::class, 'update'])->name('admin.api-config.update');
        Route::delete('api-config/{slug}', [ApiConfigController::class, 'destroy'])->name('admin.api-config.destroy');

        Route::get('import-export', [ImportExportController::class, 'index'])->name('admin.import-export.index');
        Route::get('import-export/template/{tipo}', [ImportExportController::class, 'downloadTemplate'])->name('admin.import-export.template');
        Route::get('import-export/export/{tipo}', [ImportExportController::class, 'export'])->name('admin.import-export.export');
        Route::post('import-export/upload', [ImportExportController::class, 'upload'])->name('admin.import-export.upload');
        Route::post('import-export/confirm', [ImportExportController::class, 'confirm'])->name('admin.import-export.confirm');

        // Módulo de Segurança - Restrito ao Perfil de Administrador
        Route::middleware(['admin.only'])->group(function () {
            Route::get('security', [SecurityController::class, 'index'])->name('admin.security.index');
            Route::post('security/block-ip', [SecurityController::class, 'blockIp'])->name('admin.security.block-ip');
            Route::delete('security/unblock-ip/{ip}', [SecurityController::class, 'unblockIp'])->name('admin.security.unblock-ip');
            Route::get('security/export-csv/{tipo}', [SecurityController::class, 'exportCsv'])->name('admin.security.export-csv');
            Route::post('security/profiles/{id}/permissions', [SecurityController::class, 'updatePermissions'])->name('admin.security.profiles.permissions.update');
            Route::post('security/run-migrations', [SecurityController::class, 'runMigrations'])->name('admin.security.run-migrations');
        });

        Route::resource('employees', EmployeeController::class)->names('admin.employees');
        Route::resource('clients', ClientController::class)->names('admin.clients');

        // Perfil do Usuário
        Route::get('profile', [ProfileController::class, 'show'])->name('admin.profile.show');
        Route::put('profile', [ProfileController::class, 'update'])->name('admin.profile.update');
        Route::get('marketing/coupons', [MarketingController::class, 'couponsIndex'])->name('admin.marketing.coupons');
        Route::post('marketing/coupons', [MarketingController::class, 'couponsStore'])->name('admin.marketing.coupons.store');
        Route::patch('marketing/coupons/{id}/toggle', [MarketingController::class, 'couponsToggle'])->name('admin.marketing.coupons.toggle');

        Route::get('marketing/points', [MarketingController::class, 'pointsIndex'])->name('admin.marketing.points');
        Route::get('marketing/referrals', [MarketingController::class, 'referralsIndex'])->name('admin.marketing.referrals');
        Route::get('marketing/highlights', [MarketingController::class, 'highlightsIndex'])->name('admin.marketing.highlights');
        Route::post('marketing/highlights', [MarketingController::class, 'highlightsUpdate'])->name('admin.marketing.highlights.update');

        // Configurações da Vitrine (Marketing)
        Route::get('/vitrine/banners', [StoreSettingsAdminController::class, 'bannersIndex'])->name('admin.banners.index');
        Route::post('/vitrine/banners', [StoreSettingsAdminController::class, 'bannersStore'])->name('admin.banners.store');
        Route::put('/vitrine/banners/{id}', [StoreSettingsAdminController::class, 'bannersUpdate'])->name('admin.banners.update');
        Route::delete('/vitrine/banners/{id}', [StoreSettingsAdminController::class, 'bannersDestroy'])->name('admin.banners.destroy');

        Route::get('/vitrine/beneficios', [StoreSettingsAdminController::class, 'benefitsIndex'])->name('admin.benefits.index');
        Route::post('/vitrine/beneficios', [StoreSettingsAdminController::class, 'benefitsStore'])->name('admin.benefits.store');
        Route::put('/vitrine/beneficios/{id}', [StoreSettingsAdminController::class, 'benefitsUpdate'])->name('admin.benefits.update');
        Route::delete('/vitrine/beneficios/{id}', [StoreSettingsAdminController::class, 'benefitsDestroy'])->name('admin.benefits.destroy');

        // ─── CRM Enterprise ───────────────────────────────────────────────
        Route::prefix('crm')->name('admin.crm.')->group(function () {

            // Dashboard Executivo e Comercial
            Route::get('/', [CrmDashboardController::class, 'index'])->name('dashboard');
            Route::get('/comercial', [CrmDashboardController::class, 'comercial'])->name('comercial');
            Route::get('/alertas', [CrmDashboardController::class, 'alertas'])->name('alertas');
            Route::get('/abandoned-carts', [CrmDashboardController::class, 'abandonedCarts'])->name('abandoned-carts');

            // Pipeline Kanban
            Route::get('/pipeline', [CrmPipelineController::class, 'index'])->name('pipeline');

            // Leads
            Route::get('/leads', [CrmLeadController::class, 'index'])->name('leads.index');
            Route::post('/leads', [CrmLeadController::class, 'store'])->name('leads.store');
            Route::get('/leads/{lead}', [CrmLeadController::class, 'show'])->name('leads.show');
            Route::put('/leads/{lead}', [CrmLeadController::class, 'update'])->name('leads.update');
            Route::delete('/leads/{lead}', [CrmLeadController::class, 'destroy'])->name('leads.destroy');
            Route::post('/leads/{lead}/mover-etapa', [CrmLeadController::class, 'moverEtapa'])->name('leads.mover-etapa');

            // Clientes CRM 360°
            Route::get('/clientes', [CrmClienteController::class, 'index'])->name('clientes.index');
            Route::get('/clientes/{cliente}', [CrmClienteController::class, 'show'])->name('clientes.show');
            Route::put('/clientes/{cliente}', [CrmClienteController::class, 'update'])->name('clientes.update');
            Route::post('/clientes/{cliente}/nota', [CrmClienteController::class, 'addNota'])->name('clientes.nota');
            Route::post('/clientes/{cliente}/contato', [CrmClienteController::class, 'addContato'])->name('clientes.contato');
            Route::post('/clientes/{cliente}/documento', [CrmClienteController::class, 'addDocumento'])->name('clientes.documento');

            // Tarefas
            Route::get('/tarefas', [CrmTarefaController::class, 'index'])->name('tarefas.index');
            Route::post('/tarefas', [CrmTarefaController::class, 'store'])->name('tarefas.store');
            Route::put('/tarefas/{tarefa}', [CrmTarefaController::class, 'update'])->name('tarefas.update');
            Route::patch('/tarefas/{tarefa}/concluir', [CrmTarefaController::class, 'concluir'])->name('tarefas.concluir');
            Route::delete('/tarefas/{tarefa}', [CrmTarefaController::class, 'destroy'])->name('tarefas.destroy');

            // Templates de Mensagem
            Route::get('/templates', [CrmTemplateController::class, 'index'])->name('templates.index');
            Route::post('/templates', [CrmTemplateController::class, 'store'])->name('templates.store');
            Route::put('/templates/{template}', [CrmTemplateController::class, 'update'])->name('templates.update');
            Route::delete('/templates/{template}', [CrmTemplateController::class, 'destroy'])->name('templates.destroy');
            Route::post('/templates/{template}/preview', [CrmTemplateController::class, 'preview'])->name('templates.preview');

            // Campanhas
            Route::get('/campanhas', [CrmCampanhaController::class, 'index'])->name('campanhas.index');
            Route::post('/campanhas', [CrmCampanhaController::class, 'store'])->name('campanhas.store');
            Route::put('/campanhas/{campanha}', [CrmCampanhaController::class, 'update'])->name('campanhas.update');
            Route::delete('/campanhas/{campanha}', [CrmCampanhaController::class, 'destroy'])->name('campanhas.destroy');
            Route::post('/campanhas/{campanha}/disparar', [CrmCampanhaController::class, 'disparar'])->name('campanhas.disparar');

            // Automações
            Route::get('/automacoes', [CrmAutomacaoController::class, 'index'])->name('automacoes.index');
            Route::post('/automacoes', [CrmAutomacaoController::class, 'store'])->name('automacoes.store');
            Route::put('/automacoes/{automacao}', [CrmAutomacaoController::class, 'update'])->name('automacoes.update');
            Route::delete('/automacoes/{automacao}', [CrmAutomacaoController::class, 'destroy'])->name('automacoes.destroy');

            // Segmentos
            Route::get('/segmentos', [CrmSegmentoController::class, 'index'])->name('segmentos.index');
            Route::post('/segmentos', [CrmSegmentoController::class, 'store'])->name('segmentos.store');
            Route::put('/segmentos/{segmento}', [CrmSegmentoController::class, 'update'])->name('segmentos.update');
            Route::delete('/segmentos/{segmento}', [CrmSegmentoController::class, 'destroy'])->name('segmentos.destroy');
            Route::post('/segmentos/{segmento}/recalcular', [CrmSegmentoController::class, 'recalcular'])->name('segmentos.recalcular');
        });
    });
});
