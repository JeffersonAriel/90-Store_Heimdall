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
