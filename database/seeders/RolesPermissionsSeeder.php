<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa cache de permissões
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ─── Permissões por módulo ─────────────────────────────────────────────
        $modules = [
            'dashboard', 'products', 'categories', 'brands',
            'customers', 'orders', 'stock', 'purchases',
            'financial', 'crm', 'marketing', 'reports', 'bi',
            'users', 'permissions', 'audit', 'settings',
            'ai', 'shipping', 'payments', 'api',
        ];

        $actions = ['view', 'create', 'edit', 'delete', 'export', 'import'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        // Permissões especiais
        $special = [
            'audit.view-all',
            'financial.view-sensitive',
            'users.manage-roles',
            'settings.manage-system',
            'installer.access',
        ];

        foreach ($special as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ─── Roles ────────────────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $gerente = Role::firstOrCreate(['name' => 'gerente', 'guard_name' => 'web']);
        $operador = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
        $financeiro = Role::firstOrCreate(['name' => 'financeiro', 'guard_name' => 'web']);
        $estoque = Role::firstOrCreate(['name' => 'estoque', 'guard_name' => 'web']);
        $cliente = Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);

        // Super Admin tem tudo
        $superAdmin->syncPermissions(Permission::all());

        // Admin tem tudo menos configurações de sistema
        $adminPerms = Permission::where('name', 'not like', 'settings.%')
            ->where('name', 'not like', 'installer.%')
            ->get();
        $admin->syncPermissions($adminPerms);

        // Gerente
        $gerenteModules = ['dashboard', 'products', 'categories', 'brands', 'customers', 'orders', 'stock', 'crm', 'marketing', 'reports'];
        $gerentePerms = Permission::whereIn('name', collect($gerenteModules)->flatMap(fn($m) => collect($actions)->map(fn($a) => "{$m}.{$a}"))->toArray())->get();
        $gerente->syncPermissions($gerentePerms);

        // Operador (vendas)
        $operadorPerms = Permission::whereIn('name', [
            'dashboard.view', 'products.view', 'customers.view', 'customers.create',
            'customers.edit', 'orders.view', 'orders.create', 'orders.edit',
        ])->get();
        $operador->syncPermissions($operadorPerms);

        // Financeiro
        $financeiroPerms = Permission::where('name', 'like', 'financial.%')
            ->orWhereIn('name', ['dashboard.view', 'reports.view', 'reports.export', 'orders.view'])
            ->get();
        $financeiro->syncPermissions($financeiroPerms);

        // Estoque
        $estoquePerms = Permission::where('name', 'like', 'stock.%')
            ->orWhere('name', 'like', 'purchases.%')
            ->orWhereIn('name', ['dashboard.view', 'products.view'])
            ->get();
        $estoque->syncPermissions($estoquePerms);

        // Cliente só tem permissões básicas (para área do cliente na loja)
        $cliente->syncPermissions([]);
    }
}
