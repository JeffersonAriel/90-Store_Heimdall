<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\InfinitePayService;
use App\Services\OrderStatusService;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InfinitePayController extends Controller
{
    protected $infinitePayService;
    protected $orderStatusService;
    protected $financialService;

    public function __construct(
        InfinitePayService $infinitePayService,
        OrderStatusService $orderStatusService,
        FinancialService $financialService
    ) {
        $this->infinitePayService = $infinitePayService;
        $this->orderStatusService = $orderStatusService;
        $this->financialService = $financialService;
    }

    /**
     * Processa a chamada do Webhook da InfinitePay
     */
    public function webhook(Request $request)
    {
        Log::info('InfinitePay Webhook recebido', $request->all());

        // Identificadores vindos no webhook
        $orderNsu = $request->input('order_nsu');
        $transactionNsu = $request->input('transaction_nsu');
        $slug = $request->input('slug');

        if (!$orderNsu || !$transactionNsu || !$slug) {
            Log::warning('InfinitePay Webhook: Dados incompletos no request.', $request->all());
            return response()->json(['message' => 'Parâmetros obrigatórios ausentes.'], 400);
        }

        // Localizar pedido pelo order_nsu (PED0000000X)
        // Extrai o ID numérico
        $pedidoId = (int) str_replace('PED', '', $orderNsu);
        $pedido = Pedido::find($pedidoId);

        if (!$pedido) {
            Log::warning("InfinitePay Webhook: Pedido {$orderNsu} não encontrado.");
            return response()->json(['message' => 'Pedido não encontrado.'], 404);
        }

        // Realiza o payment_check seguro no backend
        $check = $this->infinitePayService->checkPayment($orderNsu, $transactionNsu, $slug);

        if ($check['success'] && $check['paid']) {
            $checkData = $check['data'];

            DB::transaction(function () use ($pedido, $transactionNsu, $slug, $checkData) {
                // Registrar/Atualizar registro de Pagamento
                $pagamento = DB::table('pagamentos')
                    ->where('pedido_id', $pedido->id)
                    ->where('gateway', 'infinitepay')
                    ->first();

                $gatewayIdExterno = $transactionNsu;
                $payloadJson = json_encode($checkData);

                if ($pagamento) {
                    DB::table('pagamentos')->where('id', $pagamento->id)->update([
                        'gateway_id_externo' => $gatewayIdExterno,
                        'status' => 'aprovado',
                        'valor' => $pedido->total,
                        'payload_json' => $payloadJson,
                        'webhook_json' => json_encode($checkData),
                        'paid_at' => now(),
                        'updated_at' => now()
                    ]);
                } else {
                    DB::table('pagamentos')->insert([
                        'pedido_id' => $pedido->id,
                        'gateway' => 'infinitepay',
                        'gateway_id_externo' => $gatewayIdExterno,
                        'metodo' => $checkData['payment_method'] ?? 'cartao_credito',
                        'status' => 'aprovado',
                        'valor' => $pedido->total,
                        'payload_json' => $payloadJson,
                        'webhook_json' => json_encode($checkData),
                        'paid_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // Salvar dados adicionais em observações ou metadados
                $infoPagamento = "\nInfinitePay NSU: {$transactionNsu} | Slug: {$slug}";
                if (isset($checkData['receipt_url'])) {
                    $infoPagamento .= " | Comprovante: " . $checkData['receipt_url'];
                }
                $pedido->update([
                    'observacoes' => $pedido->observacoes . $infoPagamento
                ]);

                // Registrar entrada no caixa financeiro com o comprovante
                $comprovanteUrl = $checkData['receipt_url'] ?? null;
                $this->financialService->registerSaleEntry($pedido->id, $pedido->total, 'infinitepay', $comprovanteUrl);

                // Transiciona para a próxima etapa (em_separacao)
                $this->orderStatusService->transitionTo(
                    $pedido->id,
                    'em_separacao',
                    null,
                    "Pagamento confirmado via webhook InfinitePay. NSU: {$transactionNsu}."
                );
            });

            return response()->json(['message' => 'Pagamento processado com sucesso.'], 200);
        }

        Log::warning("InfinitePay Webhook: Validação falhou para o pedido {$orderNsu}. paid = false ou erro no check.");
        return response()->json(['message' => 'Pagamento não confirmado na InfinitePay.'], 400);
    }
}
