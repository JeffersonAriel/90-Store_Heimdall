<?php

namespace App\Services;

use App\Models\LancamentoFinanceiro;
use App\Models\ContaBancaria;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    /**
     * Gera lançamento automático de entrada para um pedido pago.
     */
    public function registerSaleEntry(int $orderId, float $value, string $gateway): int
    {
        return DB::transaction(function () use ($orderId, $value, $gateway) {
            // Associa à conta bancária principal ativa (ou cria uma genérica se não houver)
            $conta = ContaBancaria::where('ativa', true)->first();
            if (!$conta) {
                $conta = ContaBancaria::create([
                    'banco' => 'Gateway ' . ucfirst($gateway),
                    'titular' => '90-Store E-commerce',
                    'tipo' => 'pix',
                    'ativa' => true
                ]);
            }

            $lancamento = LancamentoFinanceiro::create([
                'conta_id' => $conta->id,
                'pedido_id' => $orderId,
                'tipo' => 'entrada',
                'categoria' => 'venda',
                'descricao' => "Recebimento do pedido #{$orderId} via {$gateway}",
                'valor' => $value,
                'data_lancamento' => now()->toDateString(),
                'data_competencia' => now()->toDateString(),
                'conciliado' => true, // Entradas automáticas de gateways já vem confirmadas/conciliadas
                'conciliado_em' => now(),
            ]);

            // Se o pedido contiver produtos próprios, o estoque já foi baixado, 
            // se contiver produtos dropshipping, agora deve-se gerar a pendência de lançamento de saída do repasse ao fornecedor
            $this->generateSupplierPayouts($orderId);

            return $lancamento->id;
        });
    }

    /**
     * Identifica itens dropshipping do pedido e lança a saída financeira correspondente para repasse ao fornecedor.
     */
    private function generateSupplierPayouts(int $orderId)
    {
        $pedido = Pedido::with('itens.produto.fornecedor')->find($orderId);
        if (!$pedido) return;

        foreach ($pedido->itens as $item) {
            if ($item->tipo_estoque_snapshot === 'dropshipping') {
                $fornecedor = $item->produto->fornecedor;
                if (!$fornecedor) continue;

                $custoTotal = $item->preco_custo_snapshot * $item->quantidade;

                // Lança saída pendente de conciliação (pago_fornecedor = false)
                LancamentoFinanceiro::create([
                    'fornecedor_id' => $fornecedor->id,
                    'pedido_id' => $orderId,
                    'tipo' => 'saida',
                    'categoria' => 'compra_fornecedor',
                    'descricao' => "Repasse Dropshipping para fornecedor: {$fornecedor->razao_social} - Item: {$item->nome_snapshot} (x{$item->quantidade})",
                    'valor' => $custoTotal,
                    'data_lancamento' => now()->toDateString(),
                    'data_competencia' => now()->toDateString(),
                    'pago_fornecedor' => false,
                ]);
            }
        }
    }

    /**
     * Conciliação manual de Pix ou lançamentos diversos
     */
    public function reconcile(int $id, int $employeeId): bool
    {
        return DB::transaction(function () use ($id, $employeeId) {
            $lancamento = LancamentoFinanceiro::lockForUpdate()->find($id);
            if (!$lancamento) return false;

            $lancamento->update([
                'conciliado' => true,
                'conciliado_por' => $employeeId,
                'conciliado_em' => now()
            ]);

            return true;
        });
    }

    /**
     * Relatório Consolidados de DRE (Demonstração de Resultado do Exercício)
     */
    public function getProfitReport(string $startDate, string $endDate): array
    {
        $entradas = LancamentoFinanceiro::where('tipo', 'entrada')
            ->whereBetween('data_lancamento', [$startDate, $endDate])
            ->sum('valor');

        $saidas = LancamentoFinanceiro::where('tipo', 'saida')
            ->whereBetween('data_lancamento', [$startDate, $endDate])
            ->sum('valor');

        // Calcula custo total das mercadorias vendidas usando o snapshot imutável de itens do pedido pagos no período
        $cmv = DB::table('itens_pedido as ip')
            ->join('pedidos as p', 'ip.pedido_id', '=', 'p.id')
            ->join('pagamentos as pag', 'pag.pedido_id', '=', 'p.id')
            ->where('pag.status', 'aprovado')
            ->whereBetween('p.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->sum(DB::raw('ip.preco_custo_snapshot * ip.quantidade'));

        $lucroLiquido = $entradas - $saidas;

        return [
            'total_receitas' => floatval($entradas),
            'total_despesas' => floatval($saidas),
            'cmv' => floatval($cmv),
            'lucro_bruto' => floatval($entradas - $cmv),
            'lucro_liquido' => floatval($lucroLiquido),
        ];
    }
}
