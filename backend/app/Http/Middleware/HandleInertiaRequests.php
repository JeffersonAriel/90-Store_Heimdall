<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the data that is shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $employee = null;
        $permissions = [];

        try {
            $employee = Auth::guard('admin')->user();
            if ($employee) {
                $employee->load('perfil');
                
                if ($employee->perfil) {
                    if ($employee->perfil->is_admin) {
                        // Admins têm acesso virtual a tudo
                        $permissions = DB::table('permissoes_modulo')
                            ->select('modulo', 'acao')
                            ->get()
                            ->toArray();
                    } else {
                        $permissions = DB::table('permissoes_modulo')
                            ->where('perfil_id', $employee->perfil_id)
                            ->select('modulo', 'acao')
                            ->get()
                            ->toArray();
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignora se o banco não estiver pronto ou sem tabelas (como no instalador)
        }

        // Estatísticas operacionais rápidas para a sidebar/topbar do painel
        $counts = [
            'pendingOrders' => 0,
            'criticalStock' => 0,
        ];

        try {
            if ($employee) {
                $counts['pendingOrders'] = DB::table('pedidos')->where('status', 'aguardando_pagamento')->count();
                $counts['criticalStock'] = DB::table('variacoes_produto')
                    ->join('produtos', 'variacoes_produto.produto_id', '=', 'produtos.id')
                    ->whereNull('produtos.deleted_at')
                    ->where('tipo_estoque', 'proprio')
                    ->whereRaw('variacoes_produto.estoque_quantidade <= produtos.estoque_critico')
                    ->count();
            }
        } catch (\Exception $e) {
            // Ignora se tabelas não existirem
        }

        return array_merge(parent::share($request), [
            'asset_url' => asset(''),
            'auth' => [
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'nome' => $employee->nome,
                    'email' => $employee->email,
                    'is_admin' => $employee->isAdmin(),
                    'perfil' => $employee->perfil ? [
                        'nome' => $employee->perfil->nome,
                    ] : null,
                ] : null,
                'permissions' => $permissions,
            ],
            'counts' => $counts,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
