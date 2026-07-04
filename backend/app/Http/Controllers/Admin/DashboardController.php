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
            'critico_estoque' => DB::table('variacoes_produto')
                ->where('tipo_estoque', 'proprio')
                ->whereRaw('estoque_quantidade <= estoque_critico')
                ->count(),
        ];

        // Alertas de estoque crítico/mínimo
        $alertasEstoque = DB::table('variacoes_produto as vp')
            ->join('produtos as p', 'vp.produto_id', '=', 'p.id')
            ->select('p.nome', 'vp.sku', 'vp.tamanho', 'vp.cor', 'vp.estoque_quantidade', 'vp.estoque_minimo', 'vp.estoque_critico')
            ->where('vp.tipo_estoque', 'proprio')
            ->where(function ($query) {
                $query->whereRaw('vp.estoque_quantidade <= vp.estoque_minimo')
                      ->orWhereRaw('vp.estoque_quantidade <= vp.estoque_critico');
            })
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'kpis' => $kpis,
            'alertasEstoque' => $alertasEstoque,
        ]);
    }
}
