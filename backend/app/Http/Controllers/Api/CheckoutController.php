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
            'endereco_id' => 'nullable|exists:enderecos_cliente,id',
            'cep' => 'required_without:endereco_id|string',
            'logradouro' => 'required_without:endereco_id|string',
            'numero' => 'required_without:endereco_id|string',
            'bairro' => 'required_without:endereco_id|string',
            'cidade' => 'required_without:endereco_id|string',
            'estado' => 'required_without:endereco_id|string',
            'gateway' => 'required|string',
            'cupom_codigo' => 'nullable|string',
            'itens' => 'required|array|min:1',
            'itens.*.variacao_id' => 'required|exists:variacoes_produto,id',
            'itens.*.quantidade' => 'required|integer|min:1',
            'frete_valor' => 'required|numeric|min:0',
            'frete_servico' => 'required|string',
        ]);

        $cliente = $request->user();

        $enderecoId = $request->endereco_id;
        if (!$enderecoId) {
            // Cria endereço se não enviou ID
            $address = $cliente->enderecos()->create([
                'cep' => $request->cep,
                'logradouro' => $request->logradouro,
                'numero' => $request->numero,
                'complemento' => $request->complemento,
                'bairro' => $request->bairro,
                'cidade' => $request->cidade,
                'estado' => $request->estado,
                'is_principal' => !$cliente->enderecos()->exists()
            ]);
            $enderecoId = $address->id;
        }

        $order = DB::transaction(function () use ($request, $cliente, $enderecoId) {
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
                'endereco_id' => $enderecoId,
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

        // Tratamento para PIX Manual
        if ($request->gateway === 'pix_manual') {
            $apiPix = \App\Models\ApiConfiguracao::where('slug', 'pix_manual')->first();
            $chavePix = '';
            if ($apiPix && $apiPix->credenciais_json) {
                $creds = json_decode($apiPix->credenciais_json, true);
                // Pega o valor do campo 'chave_pix' ou simplesmente o primeiro valor cadastrado nas credenciais
                $chavePix = $creds['chave_pix'] ?? reset($creds) ?? '';
            }

            return response()->json([
                'success' => true,
                'pedido_id' => $order->id,
                'total' => $order->total,
                'pix_manual' => true,
                'chave_pix' => $chavePix,
            ]);
        }

        // Mock Gateway Payment Success para prosseguir o fluxo sem gateway real configurado (Demo - Cartão)
        DB::table('pagamentos')->insert([
            'pedido_id' => $order->id,
            'gateway' => $request->gateway,
            'gateway_id_externo' => 'demo_' . uniqid(),
            'metodo' => 'credit',
            'status' => 'pago', // Mock como pago para testar fluxo feliz na demo
            'valor' => $order->total,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Atualiza status do pedido
        $order->update(['status' => 'pago']);
        DB::table('historico_status_pedido')->insert([
            'pedido_id' => $order->id,
            'status_novo' => 'pago',
            'observacao' => 'Pagamento aprovado via Checkout Demo.',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'pedido_id' => $order->id,
            'total' => $order->total,
        ]);
    }
}
