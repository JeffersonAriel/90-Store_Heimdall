<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Cupom;
use App\Models\VariacaoProduto;
use App\Services\StockService;
use App\Services\GatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    protected $stockService;
    protected $gatewayService;

    public function __construct(StockService $stockService, GatewayService $gatewayService)
    {
        $this->stockService = $stockService;
        $this->gatewayService = $gatewayService;
    }

    /**
     * Finaliza o pedido e gera o pagamento Pix
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'endereco_id' => 'required|exists:enderecos_cliente,id',
            'gateway' => 'required|in:mercadopago,pagseguro,stripe',
            'cupom_codigo' => 'nullable|string',
            'itens' => 'required|array|min:1',
            'itens.*.variacao_id' => 'required|exists:variacoes_produto,id',
            'itens.*.quantidade' => 'required|integer|min:1',
            'frete_valor' => 'required|numeric|min:0',
            'frete_servico' => 'required|string',
        ]);

        $cliente = $request->user();

        // Valida propriedade do endereço
        $address = $cliente->enderecos()->where('id', $request->endereco_id)->first();
        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Endereço inválido para este cliente.'], 400);
        }

        $order = DB::transaction(function () use ($request, $cliente) {
            $subtotal = 0;
            $itensDetalhados = [];

            // 1. Processa itens e valida estoques
            foreach ($request->itens as $itemInput) {
                $variation = VariacaoProduto::with('produto')->lockForUpdate()->find($itemInput['variacao_id']);
                $produto = $variation->produto;

                // Preço final
                $precoVenda = $produto->tem_desconto ? $produto->preco_desconto : $produto->preco_venda;
                $precoVenda += $variation->preco_adicional;

                $itemSubtotal = $precoVenda * $itemInput['quantidade'];
                $subtotal += $itemSubtotal;

                $itensDetalhados[] = [
                    'produto_id' => $produto->id,
                    'variacao_id' => $variation->id,
                    'nome_snapshot' => $produto->nome,
                    'sku_snapshot' => $variation->sku,
                    'tipo_estoque_snapshot' => $variation->tipo_estoque,
                    'preco_custo_snapshot' => $produto->preco_custo,
                    'preco_venda_snapshot' => $precoVenda,
                    'quantidade' => $itemInput['quantidade'],
                    'subtotal' => $itemSubtotal
                ];
            }

            // 2. Processa desconto de cupom
            $descontoCupom = 0;
            $cupom = null;
            if ($request->filled('cupom_codigo')) {
                $cupom = Cupom::where('codigo', $request->cupom_codigo)->where('ativo', true)->first();
                if ($cupom && $subtotal >= $cupom->valor_minimo_pedido) {
                    if ($cupom->tipo === 'percent') {
                        $descontoCupom = ($subtotal * $cupom->valor) / 100;
                    } elseif ($cupom->tipo === 'fixed') {
                        $descontoCupom = $cupom->valor;
                    } elseif ($cupom->tipo === 'frete') {
                        $descontoCupom = $request->frete_valor; // Anula o frete
                    }
                    $descontoCupom = min($descontoCupom, $subtotal); // Não deixa total ser negativo
                }
            }

            $total = max(0, ($subtotal - $descontoCupom) + $request->frete_valor);

            // 3. Cria o pedido
            $order = Pedido::create([
                'cliente_id' => $cliente->id,
                'endereco_id' => $request->endereco_id,
                'cupom_id' => $cupom?->id,
                'status' => 'aguardando_pagamento',
                'subtotal' => $subtotal,
                'desconto_cupom' => $descontoCupom,
                'valor_frete' => $request->frete_valor,
                'total' => $total,
                'observacoes' => "Frete selecionado: {$request->frete_servico}",
            ]);

            // 4. Salva itens com snapshot imutável e reserva estoque próprio
            foreach ($itensDetalhados as $itemData) {
                $order->itens()->create($itemData);

                // Etapa 2: Reserva estoque se for estoque próprio
                if ($itemData['tipo_estoque_snapshot'] === 'proprio') {
                    $this->stockService->reserve($itemData['variacao_id'], $itemData['quantidade'], $order->id);
                }
            }

            // Histórico de status inicial
            DB::table('historico_status_pedido')->insert([
                'pedido_id' => $order->id,
                'status_novo' => 'aguardando_pagamento',
                'observacao' => 'Pedido gerado com sucesso via checkout e-commerce.',
                'created_at' => now(),
            ]);

            return $order;
        });

        // 5. Integração com o Gateway para gerar o Pix de forma síncrona
        $pixResult = $this->gatewayService->createPixPayment($request->gateway, $order->id, $order->total);

        if ($pixResult['success']) {
            // Cria o registro na tabela pagamentos contendo data de expiração da reserva de estoque
            DB::table('pagamentos')->insert([
                'pedido_id' => $order->id,
                'gateway' => $request->gateway,
                'gateway_id_externo' => $pixResult['payment_id'],
                'metodo' => 'pix',
                'status' => 'pendente',
                'valor' => $order->total,
                'payload_json' => json_encode($pixResult),
                'expires_at' => $pixResult['expires_at'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'pedido_id' => $order->id,
                'total' => $order->total,
                'qr_code' => $pixResult['qr_code'],
                'qr_code_base64' => $pixResult['qr_code_base64'],
                'expires_at' => $pixResult['expires_at']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'O pedido foi criado, mas houve uma falha ao gerar o Pix de pagamento no gateway externo.'
        ], 500);
    }
}
