<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Exibe a tela inicial do Back-Office Heimdall (Dashboard)
     */
    public function index()
    {
        // KPIs Simulados/Reais do sistema
        $kpis = [
            'vendas_dia'   => DB::table('pedidos')
                ->where('created_at', '>=', now()->startOfDay())
                ->whereNotIn('status', ['cancelado'])
                ->sum('total'),
            'vendas_mes'   => DB::table('pedidos')
                ->where('created_at', '>=', now()->startOfMonth())
                ->whereNotIn('status', ['cancelado'])
                ->sum('total'),
            'ticket_medio' => DB::table('pedidos')
                ->whereNotIn('status', ['cancelado'])
                ->avg('total') ?? 0,
            'pedidos_pendentes' => DB::table('pedidos')
                ->where('status', 'aguardando_pagamento')
                ->count(),
            'critico_estoque' => \App\Models\Produto::query()
                ->select('produtos.id', 'produtos.estoque_critico')
                ->join('variacoes_produto', 'produtos.id', '=', 'variacoes_produto.produto_id')
                ->where('variacoes_produto.tipo_estoque', 'proprio')
                ->groupBy('produtos.id', 'produtos.estoque_critico')
                ->havingRaw('SUM(variacoes_produto.estoque_quantidade) <= produtos.estoque_critico')
                ->get()
                ->count(),
        ];

        // Alertas de estoque crítico (Global por produto)
        $alertasEstoque = \App\Models\Produto::query()
            ->select('produtos.id', 'produtos.nome', 'produtos.estoque_critico', DB::raw('SUM(variacoes_produto.estoque_quantidade) as estoque_total'))
            ->join('variacoes_produto', 'produtos.id', '=', 'variacoes_produto.produto_id')
            ->where('variacoes_produto.tipo_estoque', 'proprio')
            ->groupBy('produtos.id', 'produtos.nome', 'produtos.estoque_critico')
            ->havingRaw('SUM(variacoes_produto.estoque_quantidade) <= produtos.estoque_critico')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'kpis' => $kpis,
            'alertasEstoque' => $alertasEstoque,
        ]);
    }
}
