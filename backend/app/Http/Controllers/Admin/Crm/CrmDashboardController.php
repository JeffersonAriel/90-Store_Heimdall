<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmAlerta;
use App\Services\Crm\CrmKpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CrmDashboardController extends Controller
{
    /**
     * Dashboard executivo CRM com KPIs em tempo real.
     */
    public function index(Request $request)
    {
        $periodo = $request->input('periodo', '30');

        $kpis          = CrmKpiService::getDashboardKpis($periodo);
        $rankingVend   = CrmKpiService::rankingVendedores((int) $periodo);
        $rankingClients= CrmKpiService::rankingClientes((int) $periodo);
        $evolucao      = CrmKpiService::evolucaoReceita((int) $periodo);

        $usuario = Auth::guard('admin')->user();

        // Alertas do usuário logado (não lidos)
        $alertas = CrmAlerta::with(['cliente:id,nome_completo', 'lead:id,nome'])
            ->where(function ($q) use ($usuario) {
                $q->where('responsavel_id', $usuario->id)
                  ->orWhereNull('responsavel_id');
            })
            ->where('resolvido', false)
            ->orderByRaw("CASE prioridade WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Crm/Dashboard', [
            'kpis'           => $kpis,
            'rankingVend'    => $rankingVend,
            'rankingClientes'=> $rankingClients,
            'evolucaoReceita'=> $evolucao,
            'alertas'        => $alertas,
            'periodo'        => $periodo,
        ]);
    }

    /**
     * Dashboard comercial: pipeline, funil, receita prevista vs realizada.
     */
    public function comercial(Request $request)
    {
        $periodo = $request->input('periodo', '30');

        // Pipeline por etapa
        $pipeline = \App\Models\Crm\CrmPipelineEtapa::withCount([
            'leads as total_leads' => fn($q) => $q->where('status', 'ativo'),
        ])->withSum(
            ['leads as valor_total' => fn($q) => $q->where('status', 'ativo')],
            'valor_esperado'
        )->ativo()->get();

        // Receita prevista (leads ativos × probabilidade)
        $receitaPrevista = \App\Models\Crm\CrmLead::where('status', 'ativo')
            ->whereNotNull('valor_esperado')
            ->selectRaw('SUM(valor_esperado * probabilidade / 100) as prevista, SUM(valor_esperado) as total')
            ->first();

        $rankingProdutos = \Illuminate\Support\Facades\DB::table('itens_pedido')
            ->join('pedidos', 'itens_pedido.pedido_id', '=', 'pedidos.id')
            ->join('produtos', 'itens_pedido.produto_id', '=', 'produtos.id')
            ->where('pedidos.created_at', '>=', now()->subDays((int) $periodo))
            ->whereNotIn('pedidos.status', ['cancelado'])
            ->whereNull('pedidos.deleted_at')
            ->selectRaw('produtos.id, produtos.nome, SUM(itens_pedido.quantidade) as qtd_vendida, SUM(itens_pedido.subtotal) as receita')
            ->groupBy('produtos.id', 'produtos.nome')
            ->orderByDesc('receita')
            ->limit(10)
            ->get();

        return Inertia::render('Crm/Comercial', [
            'pipeline'        => $pipeline,
            'receitaPrevista' => $receitaPrevista,
            'rankingProdutos' => $rankingProdutos,
            'rankingVend'     => CrmKpiService::rankingVendedores((int) $periodo),
            'rankingClientes' => CrmKpiService::rankingClientes((int) $periodo),
            'periodo'         => $periodo,
        ]);
    }

    /**
     * Lista de alertas inteligentes.
     */
    public function alertas(Request $request)
    {
        $usuario = Auth::guard('admin')->user();

        $alertas = CrmAlerta::with(['cliente:id,nome_completo,email', 'lead:id,nome'])
            ->where(function ($q) use ($usuario) {
                $q->where('responsavel_id', $usuario->id)
                  ->orWhereNull('responsavel_id');
            })
            ->when($request->input('tipo'), fn($q, $tipo) => $q->where('tipo', $tipo))
            ->when($request->boolean('apenas_nao_lidos'), fn($q) => $q->where('lido', false))
            ->where('resolvido', false)
            ->orderByRaw("CASE prioridade WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return response()->json(['success' => true, 'alertas' => $alertas]);
    }

    /**
     * Retorna a lista de carrinhos/pedidos abandonados (aguardando pagamento)
     */
    public function abandonedCarts()
    {
        $carts = \App\Models\Pedido::where('status', 'aguardando_pagamento')
            ->with([
                'cliente',
                'endereco',
                'itens.produto'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'carts' => $carts
        ]);
    }
}
