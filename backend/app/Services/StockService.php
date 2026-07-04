<?php

namespace App\Services;

use App\Models\VariacaoProduto;
use App\Models\MovimentacaoEstoque;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Etapa 2: Reserva o estoque da variação quando o pedido é criado.
     * Incrementa estoque_reservado.
     */
    public function reserve(int $variationId, int $quantity, int $orderId): bool
    {
        return DB::transaction(function () use ($variationId, $quantity, $orderId) {
            $variation = VariacaoProduto::lockForUpdate()->find($variationId);

            if (!$variation || $variation->tipo_estoque !== 'proprio') {
                return true; // Dropshipping pula controle local de estoque
            }

            // Estoque real disponível = quantidade - reservado
            $disponivel = $variation->estoque_quantidade - $variation->estoque_reservado;

            if ($disponivel < $quantity) {
                throw new \Exception("Estoque insuficiente para a variação SKU: {$variation->sku}. Disponível: {$disponivel}, Solicitado: {$quantity}");
            }

            $estoqueAntes = $variation->estoque_quantidade;

            // Incrementa o reservado
            $variation->increment('estoque_reservado', $quantity);

            // Registra a movimentação de reserva
            MovimentacaoEstoque::create([
                'variacao_id' => $variationId,
                'pedido_id' => $orderId,
                'quantidade' => -$quantity,
                'estoque_antes' => $estoqueAntes,
                'estoque_depois' => $variation->estoque_quantidade, // Permanece igual pois a baixa física só ocorre na aprovação
                'tipo' => 'reserva',
                'motivo' => "Estoque reservado devido à criação do pedido #{$orderId}",
            ]);

            return true;
        });
    }

    /**
     * Etapa 3: Efetua a baixa definitiva do estoque quando o pagamento é aprovado.
     * Decrementa estoque_quantidade e decrementa estoque_reservado.
     */
    public function confirmDebit(int $variationId, int $quantity, int $orderId, ?int $employeeId = null): bool
    {
        return DB::transaction(function () use ($variationId, $quantity, $orderId, $employeeId) {
            $variation = VariacaoProduto::lockForUpdate()->find($variationId);

            if (!$variation || $variation->tipo_estoque !== 'proprio') {
                return true; // Dropshipping pula controle
            }

            $estoqueAntes = $variation->estoque_quantidade;
            $novoEstoque = max(0, $variation->estoque_quantidade - $quantity);

            // Atualiza fisicamente a quantidade e remove da reserva
            $variation->update([
                'estoque_quantidade' => $novoEstoque,
                'estoque_reservado' => max(0, $variation->estoque_reservado - $quantity)
            ]);

            // Registra a baixa definitiva
            MovimentacaoEstoque::create([
                'variacao_id' => $variationId,
                'pedido_id' => $orderId,
                'funcionario_id' => $employeeId,
                'quantidade' => -$quantity,
                'estoque_antes' => $estoqueAntes,
                'estoque_depois' => $novoEstoque,
                'tipo' => 'baixa_confirmada',
                'motivo' => "Baixa de estoque confirmada após aprovação do pagamento do pedido #{$orderId}",
            ]);

            return true;
        });
    }

    /**
     * Devolve a reserva de estoque caso o pagamento expire, seja recusado ou cancelado.
     * Decrementa estoque_reservado.
     */
    public function release(int $variationId, int $quantity, int $orderId): bool
    {
        return DB::transaction(function () use ($variationId, $quantity, $orderId) {
            $variation = VariacaoProduto::lockForUpdate()->find($variationId);

            if (!$variation || $variation->tipo_estoque !== 'proprio') {
                return true; // Dropshipping
            }

            $estoqueAntes = $variation->estoque_quantidade;

            // Remove a reserva
            $variation->update([
                'estoque_reservado' => max(0, $variation->estoque_reservado - $quantity)
            ]);

            // Registra a liberação
            MovimentacaoEstoque::create([
                'variacao_id' => $variationId,
                'pedido_id' => $orderId,
                'quantidade' => $quantity,
                'estoque_antes' => $estoqueAntes,
                'estoque_depois' => $variation->estoque_quantidade,
                'tipo' => 'liberacao_reserva',
                'motivo' => "Estoque reservado liberado devido ao cancelamento ou expiração do pedido #{$orderId}",
            ]);

            return true;
        });
    }

    /**
     * Realiza um ajuste manual direto de estoque com motivo obrigatório.
     */
    public function manualAdjustment(int $variationId, int $newQuantity, string $reason, int $employeeId): bool
    {
        return DB::transaction(function () use ($variationId, $newQuantity, $reason, $employeeId) {
            $variation = VariacaoProduto::lockForUpdate()->find($variationId);

            if (!$variation || $variation->tipo_estoque !== 'proprio') {
                throw new \Exception("Ajuste manual indisponível. Esta variação é em modelo Dropshipping.");
            }

            $estoqueAntes = $variation->estoque_quantidade;

            $variation->update(['estoque_quantidade' => $newQuantity]);

            MovimentacaoEstoque::create([
                'variacao_id' => $variationId,
                'funcionario_id' => $employeeId,
                'quantidade' => $newQuantity - $estoqueAntes,
                'estoque_antes' => $estoqueAntes,
                'estoque_depois' => $newQuantity,
                'tipo' => 'ajuste_manual',
                'motivo' => $reason,
            ]);

            return true;
        });
    }
}
