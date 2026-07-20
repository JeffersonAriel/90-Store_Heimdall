<?php

namespace App\Services\Crm;

use Illuminate\Support\Facades\DB;

/**
 * CrmKpiService
 *
 * Calcula todos os KPIs do CRM Dashboard: LTV, CAC, Churn Rate,
 * Ticket Médio, Receita por vendedor, Taxa de recompra, etc.
 */
class CrmKpiService
{
    public static function getDashboardKpis(string $periodo = '30'): array
    {
        $inicio = now()->subDays((int) $periodo)->startOfDay();

        // Clientes
        $totalAtivos     = DB::table('clientes')->where('ativo', true)->whereNull('deleted_at')->count();
        $novosNoPeriodo  = DB::table('clientes')->where('created_at', '>=', $inicio)->whereNull('deleted_at')->count();
        $comPedido       = DB::table('clientes')->whereNull('deleted_at')->where('total_pedidos_count', '>', 0)->count();
        $recorrentes     = DB::table('clientes')->whereNull('deleted_at')->where('total_pedidos_count', '>', 1)->count();

        // Pedidos
        $pedidosQuery = DB::table('pedidos')
            ->where('created_at', '>=', $inicio)
            ->whereNotIn('status', ['aguardando_pagamento', 'cancelado']);

        $receitaRealizada = (clone $pedidosQuery)->sum('total');
        $ticketMedio      = (clone $pedidosQuery)->avg('total') ?? 0;
        $pedidosCount     = (clone $pedidosQuery)->count();

        $pedidosAbertos = DB::table('pedidos')
            ->whereNotIn('status', ['entregue', 'cancelado', 'devolvido'])
            ->count();

        // Propostas / Leads
        $leadsAtivos    = DB::table('crm_leads')->where('status', 'ativo')->whereNull('deleted_at')->count();
        $leadsConv      = DB::table('crm_leads')->where('status', 'convertido')->where('updated_at', '>=', $inicio)->count();
        $leadsTotais    = DB::table('crm_leads')->where('created_at', '>=', $inicio)->whereNull('deleted_at')->count();
        $conversao      = $leadsTotais > 0 ? round(($leadsConv / $leadsTotais) * 100, 1) : 0;

        // NPS
        $npsMedia = DB::table('crm_satisfacao_respostas')
            ->whereNotNull('nps_score')
            ->avg('nps_score');

        // Churn Rate estimado (clientes sem compra há > 90 dias / total ativos)
        $inativos90 = DB::table('clientes')
            ->whereNull('deleted_at')
            ->where('ativo', true)
            ->whereNotNull('ultimo_pedido_em')
            ->where('ultimo_pedido_em', '<', now()->subDays(90))
            ->count();
        $churnRate = $totalAtivos > 0 ? round(($inativos90 / $totalAtivos) * 100, 1) : 0;

        // LTV médio
        $ltvMedio = DB::table('clientes')
            ->whereNull('deleted_at')
            ->where('total_pedidos_count', '>', 0)
            ->avg('ltv') ?? 0;

        // Alertas ativos não resolvidos
        $alertasAtivos = DB::table('crm_alertas')->where('resolvido', false)->count();

        // Tarefas pendentes do dia
        $tarefasHoje = DB::table('crm_tarefas')
            ->whereIn('status', ['pendente', 'em_andamento'])
            ->whereNull('deleted_at')
            ->where('vencimento_em', '<=', now()->endOfDay())
            ->count();

        // Contatos realizados no período
        $contatosRealizados = DB::table('crm_contatos')
            ->where('realizado_em', '>=', $inicio)
            ->count();

        // Taxa de recompra
        $recompras = DB::table('clientes')
            ->whereNull('deleted_at')
            ->where('total_pedidos_count', '>=', 2)
            ->count();
        $taxaRecompra = $comPedido > 0 ? round(($recompras / $comPedido) * 100, 1) : 0;

        return [
            // Clientes
            'clientes_ativos'         => $totalAtivos,
            'clientes_novos'          => $novosNoPeriodo,
            'clientes_inativos_90'    => $inativos90,
            'clientes_recorrentes'    => $recorrentes,
            'churn_rate'              => $churnRate,
            'taxa_recompra'           => $taxaRecompra,

            // Receita
            'receita_realizada'       => round($receitaRealizada, 2),
            'ticket_medio'            => round($ticketMedio, 2),
            'ltv_medio'               => round($ltvMedio, 2),

            // Pedidos
            'pedidos_periodo'         => $pedidosCount,
            'pedidos_abertos'         => $pedidosAbertos,

            // Leads / Pipeline
            'leads_ativos'            => $leadsAtivos,
            'taxa_conversao'          => $conversao,

            // Satisfação
            'nps_medio'               => $npsMedia ? round($npsMedia, 1) : null,

            // Operacional
            'alertas_ativos'          => $alertasAtivos,
            'tarefas_hoje'            => $tarefasHoje,
            'contatos_realizados'     => $contatosRealizados,

            // Meta info
            'periodo_dias'            => $periodo,
            'calculado_em'            => now()->toISOString(),
        ];
    }

    /**
     * Recalcula e atualiza os campos CRM de um cliente específico.
     */
    public static function recalcularCliente(int $clienteId): void
    {
        $pedidos = DB::table('pedidos')
            ->where('cliente_id', $clienteId)
            ->whereNotIn('status', ['aguardando_pagamento', 'cancelado'])
            ->whereNull('deleted_at')
            ->orderBy('created_at')
            ->get(['id', 'total', 'created_at']);

        if ($pedidos->isEmpty()) return;

        $total      = $pedidos->sum('total');
        $count      = $pedidos->count();
        $ticket     = $total / $count;
        $primeiro   = $pedidos->first()->created_at;
        $ultimo     = $pedidos->last()->created_at;

        // Média de dias entre compras
        $mediaDias = null;
        if ($count >= 2) {
            $datas = $pedidos->pluck('created_at')->values();
            $soma  = 0;
            for ($i = 1; $i < $datas->count(); $i++) {
                $soma += \Carbon\Carbon::parse($datas[$i - 1])
                    ->diffInDays(\Carbon\Carbon::parse($datas[$i]));
            }
            $mediaDias = (int) round($soma / ($count - 1));
        }

        // Risco de churn estimado
        $diasSemCompra = \Carbon\Carbon::parse($ultimo)->diffInDays(now());
        $riscoChurn = match (true) {
            $diasSemCompra > 90 => 'alto',
            $diasSemCompra > 45 => 'medio',
            default             => 'baixo',
        };

        // NPS médio do cliente
        $npsMedia = DB::table('crm_satisfacao_respostas')
            ->where('cliente_id', $clienteId)
            ->whereNotNull('nps_score')
            ->avg('nps_score');

        DB::table('clientes')->where('id', $clienteId)->update([
            'total_pedidos_count'      => $count,
            'total_gasto'              => $total,
            'ltv'                      => $total,
            'ticket_medio_crm'         => round($ticket, 2),
            'primeiro_pedido_em'       => $primeiro,
            'ultimo_pedido_em'         => $ultimo,
            'media_dias_entre_compras' => $mediaDias,
            'risco_churn'              => $riscoChurn,
            'nps_score_medio'          => $npsMedia ? round($npsMedia, 1) : null,
        ]);
    }

    /**
     * Ranking dos vendedores por receita.
     */
    public static function rankingVendedores(int $dias = 30): array
    {
        return DB::table('clientes')
            ->join('funcionarios', 'clientes.vendedor_id', '=', 'funcionarios.id')
            ->join('pedidos', 'clientes.id', '=', 'pedidos.cliente_id')
            ->where('pedidos.created_at', '>=', now()->subDays($dias))
            ->whereNotIn('pedidos.status', ['cancelado'])
            ->whereNull('pedidos.deleted_at')
            ->select(
                'funcionarios.id',
                'funcionarios.nome',
                DB::raw('COUNT(pedidos.id) as total_pedidos'),
                DB::raw('SUM(pedidos.total) as receita_total'),
                DB::raw('AVG(pedidos.total) as ticket_medio'),
                DB::raw('COUNT(DISTINCT clientes.id) as total_clientes')
            )
            ->groupBy('funcionarios.id', 'funcionarios.nome')
            ->orderByDesc('receita_total')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Ranking de clientes por valor gasto.
     */
    public static function rankingClientes(int $dias = 30): array
    {
        return DB::table('clientes')
            ->join('pedidos', 'clientes.id', '=', 'pedidos.cliente_id')
            ->where('pedidos.created_at', '>=', now()->subDays($dias))
            ->whereNotIn('pedidos.status', ['cancelado'])
            ->whereNull('pedidos.deleted_at')
            ->whereNull('clientes.deleted_at')
            ->select(
                'clientes.id',
                'clientes.nome_completo',
                'clientes.segmento_crm',
                'clientes.email',
                DB::raw('COUNT(pedidos.id) as total_pedidos'),
                DB::raw('SUM(pedidos.total) as total_gasto')
            )
            ->groupBy('clientes.id', 'clientes.nome_completo', 'clientes.segmento_crm', 'clientes.email')
            ->orderByDesc('total_gasto')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Evolução de receita por dia nos últimos N dias.
     */
    public static function evolucaoReceita(int $dias = 30): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', created_at)"
            : "DATE(created_at)";

        return DB::table('pedidos')
            ->where('created_at', '>=', now()->subDays($dias))
            ->whereNotIn('status', ['cancelado'])
            ->whereNull('deleted_at')
            ->selectRaw("{$dateExpr} as data, SUM(total) as receita, COUNT(*) as pedidos")
            ->groupByRaw($dateExpr)
            ->orderBy('data')
            ->get()
            ->toArray();
    }
}
