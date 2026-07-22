<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    /**
     * Retorna os pedidos do cliente autenticado.
     */
    public function index(Request $request)
    {
        $pedidos = Pedido::where('cliente_id', $request->user()->id)
            ->with(['itens.produto.fotoCapa', 'itens.variacao', 'pagamentos'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'pedidos' => $pedidos
        ]);
    }

    /**
     * Retorna os detalhes de um pedido específico do cliente.
     */
    public function show(Request $request, $id)
    {
        $pedido = Pedido::where('cliente_id', $request->user()->id)
            ->where('id', $id)
            ->with([
                'itens.produto.fotoCapa', 
                'itens.variacao',
                'endereco',
                'pagamentos',
                'historicoStatus' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])
            ->first();

        if (!$pedido) {
            return response()->json(['success' => false, 'message' => 'Pedido não encontrado.'], 404);
        }

        return response()->json([
            'success' => true,
            'pedido' => $pedido
        ]);
    }

    /**
     * Retorna os detalhes públicos/seguros de um pedido pelo ID para a página de sucesso pós-compra.
     */
    public function showPublic(Request $request, $id)
    {
        $pedido = Pedido::with([
            'itens.produto.fotoCapa',
            'itens.variacao',
            'endereco',
            'pagamentos' => function($q) {
                $q->orderBy('created_at', 'desc');
            }
        ])->find($id);

        if (!$pedido) {
            return response()->json(['success' => false, 'message' => 'Pedido não encontrado.'], 404);
        }

        // Se o pedido está aguardando pagamento e recebemos os dados de transação no redirecionamento
        if ($pedido->status === 'aguardando_pagamento' && $request->filled('transaction_nsu') && $request->filled('slug')) {
            $orderNsu = 'PED' . str_pad($pedido->id, 8, '0', STR_PAD_LEFT);
            $transactionNsu = $request->input('transaction_nsu');
            $slug = $request->input('slug');

            $infinitePayService = app(\App\Services\InfinitePayService::class);
            $check = $infinitePayService->checkPayment($orderNsu, $transactionNsu, $slug);

            if ($check['success'] && $check['paid']) {
                $checkData = $check['data'];
                \Illuminate\Support\Facades\DB::transaction(function () use ($pedido, $transactionNsu, $slug, $checkData) {
                    $pagamento = \Illuminate\Support\Facades\DB::table('pagamentos')
                        ->where('pedido_id', $pedido->id)
                        ->where('gateway', 'infinitepay')
                        ->first();

                    $gatewayIdExterno = $transactionNsu;
                    $payloadJson = json_encode($checkData);

                    if ($pagamento) {
                        \Illuminate\Support\Facades\DB::table('pagamentos')->where('id', $pagamento->id)->update([
                            'gateway_id_externo' => $gatewayIdExterno,
                            'status' => 'aprovado',
                            'valor' => $pedido->total,
                            'payload_json' => $payloadJson,
                            'webhook_json' => json_encode($checkData),
                            'paid_at' => now(),
                            'updated_at' => now()
                        ]);
                    } else {
                        \Illuminate\Support\Facades\DB::table('pagamentos')->insert([
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

                    $comprovanteUrl = $checkData['receipt_url'] ?? "https://pay.infinitepay.io/receipt/{$transactionNsu}";

                    $infoPagamento = "\nInfinitePay NSU: {$transactionNsu} | Slug: {$slug} | Comprovante: {$comprovanteUrl}";
                    $pedido->update([
                        'observacoes' => trim(($pedido->observacoes ?? '') . $infoPagamento),
                        'url_comprovante_pagamento' => $comprovanteUrl,
                    ]);

                    app(\App\Services\FinancialService::class)->registerSaleEntry($pedido->id, $pedido->total, 'infinitepay', $comprovanteUrl);
                    app(\App\Services\OrderStatusService::class)->transitionTo(
                        $pedido->id,
                        'em_separacao',
                        null,
                        "Pagamento confirmado via retorno do checkout InfinitePay. NSU: {$transactionNsu}."
                    );
                });

                $pedido->refresh();
                $pedido->load(['itens.produto.fotoCapa', 'itens.variacao', 'endereco', 'pagamentos']);
            }
        }

        // Limpa dados extremamente sensíveis do cliente, mas permite identificar as informações exigidas na página
        $cliente = $pedido->cliente;

        return response()->json([
            'success' => true,
            'pedido' => [
                'id' => $pedido->id,
                'order_nsu' => 'PED' . str_pad($pedido->id, 8, '0', STR_PAD_LEFT),
                'total' => $pedido->total,
                'status' => $pedido->status,
                'valor_frete' => $pedido->valor_frete,
                'desconto_cupom' => $pedido->desconto_cupom,
                'desconto_pontos' => $pedido->desconto_pontos,
                'subtotal' => $pedido->subtotal,
                'itens' => $pedido->itens,
                'endereco' => $pedido->endereco,
                'pagamentos' => $pedido->pagamentos,
                'cliente' => $cliente ? [
                    'nome_completo' => $cliente->nome_completo,
                    'email' => $cliente->email,
                    'telefone' => $cliente->telefone ?? $cliente->whatsapp
                ] : null
            ]
        ]);
    }

    /**
     * Retorna a chave pix manual para o botão Pagar Agora
     */
    public function pixKey()
    {
        $apiPix = \App\Models\ApiConfiguracao::where('slug', 'pix_manual')->first();
        $chavePix = '';
        if ($apiPix && $apiPix->credenciais_json) {
            $creds = json_decode($apiPix->credenciais_json, true);
            $chavePix = $creds['chave_pix'] ?? reset($creds) ?? '';
        }

        return response()->json([
            'chave_pix' => $chavePix
        ]);
    }
}
