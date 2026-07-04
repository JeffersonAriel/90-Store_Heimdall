<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\StockService;
use App\Models\Pedido;

class LiberarReservasExpiradas extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stock:release-expired';

    /**
     * The console command description.
     */
    protected $description = 'Libera o estoque reservado de pedidos cujo pagamento expirou ou não foi confirmado no prazo';

    /**
     * Execute the console command.
     */
    public function handle(StockService $stockService)
    {
        $this->info('Iniciando varredura de reservas expiradas...');

        // Busca pagamentos com status 'pendente' cuja validade (expires_at) expirou
        $expiredPayments = DB::table('pagamentos')
            ->where('status', 'pendente')
            ->where('expires_at', '<', now())
            ->get();

        if ($expiredPayments->isEmpty()) {
            $this->info('Nenhuma reserva expirada encontrada.');
            return 0;
        }

        $count = 0;

        foreach ($expiredPayments as $payment) {
            DB::transaction(function () use ($payment, $stockService, &$count) {
                // Atualiza status do pagamento para expirado
                DB::table('pagamentos')->where('id', $payment->id)->update([
                    'status' => 'expirado',
                    'updated_at' => now(),
                ]);

                // Atualiza o pedido para cancelado
                $pedido = Pedido::find($payment->pedido_id);
                if ($pedido && $pedido->status === 'aguardando_pagamento') {
                    $pedido->update([
                        'status' => 'cancelado',
                        'motivo_cancelamento' => 'Pagamento não efetuado dentro do prazo de validade.',
                        'cancelado_em' => now(),
                    ]);

                    // Histórico de status
                    DB::table('historico_status_pedido')->insert([
                        'pedido_id' => $pedido->id,
                        'status_anterior' => 'aguardando_pagamento',
                        'status_novo' => 'cancelado',
                        'observacao' => 'Cancelado automaticamente devido à expiração do pagamento.',
                        'created_at' => now(),
                    ]);

                    // Libera estoque das variações próprias vinculadas ao pedido
                    $itens = DB::table('itens_pedido')->where('pedido_id', $pedido->id)->get();
                    foreach ($itens as $item) {
                        if ($item->variacao_id) {
                            $stockService->release($item->variacao_id, $item->quantidade, $pedido->id);
                        }
                    }

                    $count++;
                }
            });
        }

        $this->info("Sucesso: {$count} reserva(s) expirada(s) liberada(s) e devolvida(s) ao estoque disponível.");
        return 0;
    }
}
