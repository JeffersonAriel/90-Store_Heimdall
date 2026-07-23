<?php

namespace App\Services;

use App\Models\Pedido;
use App\Services\StockService;
use App\Services\FinancialService;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    protected $stockService;
    protected $financialService;

    // Sequência obrigatória de progressão de status do pedido
    protected $statusSequence = [
        'aguardando_pagamento',
        'em_separacao',
        'em_envio',
        'enviado',
        'entregue'
    ];

    public function __construct(StockService $stockService, FinancialService $financialService)
    {
        $this->stockService = $stockService;
        $this->financialService = $financialService;
    }

    /**
     * Atualiza o status do pedido garantindo a sequência obrigatória de estados.
     */
    public function transitionTo(int $orderId, string $newStatus, ?int $employeeId = null, ?string $observation = null): bool
    {
        return DB::transaction(function () use ($orderId, $newStatus, $employeeId, $observation) {
            $pedido = Pedido::lockForUpdate()->findOrFail($orderId);
            $currentStatus = $pedido->status;

            if ($currentStatus === $newStatus) {
                return true;
            }

            // Exceções aceitas em qualquer momento: cancelado, devolvido
            if (in_array($newStatus, ['cancelado', 'devolvido'])) {
                $this->handleSpecialTransition($pedido, $newStatus, $currentStatus, $employeeId);
                $this->saveHistory($orderId, $currentStatus, $newStatus, $employeeId, $observation);
                return true;
            }

            // Valida sequência obrigatória
            $currentIndex = array_search($currentStatus, $this->statusSequence);
            $newIndex = array_search($newStatus, $this->statusSequence);

            if ($currentIndex === false || $newIndex === false) {
                throw new \Exception("Transição de status inválida ou desconhecida de '{$currentStatus}' para '{$newStatus}'.");
            }

            // Exige progressão sequencial exata (não permite pular etapas, ex: de aguardando_pagamento direto para enviado)
            if ($newIndex !== $currentIndex + 1) {
                throw new \Exception("Progressão de status do pedido deve ser sequencial e exata. Próximo status esperado: '{$this->statusSequence[$currentIndex + 1]}'.");
            }

            // Tratamento lógico de transições específicas
            if ($newStatus === 'em_separacao') {
                // Pagamento confirmado/aprovado. Dá a baixa física definitiva do estoque reservado
                $this->debitStockForOrder($pedido, $employeeId);
            }

            $pedido->update(['status' => $newStatus]);
            $this->saveHistory($orderId, $currentStatus, $newStatus, $employeeId, $observation);

            // Dispara e-mail automático ao cliente sobre a atualização do pedido via Titan Mail HostGator
            try {
                if ($pedido->cliente && !empty($pedido->cliente->email)) {
                    \Illuminate\Support\Facades\Mail::to($pedido->cliente->email)
                        ->send(new \App\Mail\OrderStatusUpdatedMail($pedido));
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Falha ao enviar e-mail de atualização do pedido #{$pedido->id}: " . $e->getMessage());
            }

            return true;
        });
    }

    /**
     * Avança o pedido sequencialmente até o status de destino (ex: em_separacao -> em_envio -> enviado -> entregue).
     */
    public function advanceToStatus(int $orderId, string $targetStatus, ?int $employeeId = null, ?string $observation = null): bool
    {
        $pedido = Pedido::find($orderId);
        if (!$pedido) return false;

        $currentStatus = $pedido->status;

        if ($currentStatus === $targetStatus) {
            return true;
        }

        // Caso especial: cancelado ou devolvido
        if (in_array($targetStatus, ['cancelado', 'devolvido'])) {
            return $this->transitionTo($orderId, $targetStatus, $employeeId, $observation);
        }

        $currentIndex = array_search($currentStatus, $this->statusSequence);
        $targetIndex  = array_search($targetStatus, $this->statusSequence);

        if ($currentIndex === false || $targetIndex === false) {
            return false;
        }

        if ($targetIndex <= $currentIndex) {
            // Não regride status já alcançados
            return false;
        }

        // Executa cada transição da sequência passo a passo
        for ($i = $currentIndex + 1; $i <= $targetIndex; $i++) {
            $nextStatus = $this->statusSequence[$i];
            $obsStep = ($i === $targetIndex && $observation) ? $observation : "Avanço automático para {$nextStatus}.";
            $this->transitionTo($orderId, $nextStatus, $employeeId, $obsStep);
        }

        return true;
    }

    /**
     * Confirmação manual de pagamento (PIX direto no banco, etc.)
     */
    public function confirmPaymentManually(int $orderId, int $employeeId, string $obs): bool
    {
        return DB::transaction(function () use ($orderId, $employeeId, $obs) {
            $pedido = Pedido::lockForUpdate()->findOrFail($orderId);

            if ($pedido->status !== 'aguardando_pagamento') {
                throw new \Exception("Este pedido já possui confirmação ou transição de pagamento realizada.");
            }

            // Encontra ou cria um registro de pagamento correspondente
            $pagamento = DB::table('pagamentos')
                ->where('pedido_id', $orderId)
                ->where('status', 'pendente')
                ->first();

            $pagamentoId = $pagamento ? $pagamento->id : DB::table('pagamentos')->insertGetId([
                'pedido_id' => $orderId,
                'gateway' => 'manual',
                'metodo' => 'pix',
                'status' => 'pendente',
                'valor' => $pedido->total,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Grava confirmação manual de auditoria
            DB::table('confirmacoes_pagamento_manual')->insert([
                'pedido_id' => $orderId,
                'pagamento_id' => $pagamentoId,
                'funcionario_id' => $employeeId,
                'observacao' => $obs,
                'confirmed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Atualiza status do pagamento
            DB::table('pagamentos')->where('id', $pagamentoId)->update([
                'status' => 'aprovado',
                'paid_at' => now(),
                'updated_at' => now()
            ]);

            // Registra entrada no caixa financeiro
            $this->financialService->registerSaleEntry($orderId, $pedido->total, 'manual');

            // Transiciona obrigatoriamente para a próxima etapa (em_separacao)
            $this->transitionTo($orderId, 'em_separacao', $employeeId, "Pagamento confirmado manualmente por funcionário. Obs: {$obs}");

            return true;
        });
    }

    private function debitStockForOrder(Pedido $pedido, ?int $employeeId)
    {
        $itens = DB::table('itens_pedido')->where('pedido_id', $pedido->id)->get();
        foreach ($itens as $item) {
            if ($item->variacao_id) {
                $this->stockService->confirmDebit($item->variacao_id, $item->quantidade, $pedido->id, $employeeId);
            }
        }
    }

    private function handleSpecialTransition(Pedido $pedido, string $newStatus, string $currentStatus, ?int $employeeId)
    {
        if ($newStatus === 'cancelado') {
            $pedido->update([
                'status' => 'cancelado',
                'cancelado_por' => $employeeId,
                'cancelado_em' => now(),
            ]);

            // Libera estoque reservado de volta se o pedido estava aguardando pagamento
            if ($currentStatus === 'aguardando_pagamento') {
                $itens = DB::table('itens_pedido')->where('pedido_id', $pedido->id)->get();
                foreach ($itens as $item) {
                    if ($item->variacao_id) {
                        $this->stockService->release($item->variacao_id, $item->quantidade, $pedido->id);
                    }
                }
            }
        } elseif ($newStatus === 'devolvido') {
            $pedido->update(['status' => 'devolvido']);
        }
    }

    private function saveHistory(int $orderId, ?string $oldStatus, string $newStatus, ?int $employeeId, ?string $observation)
    {
        DB::table('historico_status_pedido')->insert([
            'pedido_id' => $orderId,
            'status_anterior' => $oldStatus,
            'status_novo' => $newStatus,
            'funcionario_id' => $employeeId,
            'observacao' => $observation ?? "Alterado de {$oldStatus} para {$newStatus}",
            'created_at' => now(),
        ]);
    }
}
