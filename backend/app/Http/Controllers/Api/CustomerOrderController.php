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
            ->with(['itens.produto.fotoCapa', 'itens.variacao'])
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
