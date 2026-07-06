<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $statusService;

    public function __construct(OrderStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $orders = Pedido::query()
            ->with(['cliente'])
            ->when($search, function ($query, $search) {
                $query->where('id', $search)
                      ->orWhereHas('cliente', function ($q) use ($search) {
                          $q->where('nome_completo', 'like', "%{$search}%");
                      });
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only('search', 'status')
        ]);
    }

    public function show(int $id)
    {
        $order = Pedido::with([
            'cliente', 
            'endereco', 
            'itens.produto', 
            'pagamentos', 
            'historicoStatus.funcionario'
        ])->findOrFail($id);

        // Gera a mensagem pré-formatada do WhatsApp baseada no status atual
        $waLink = $this->generateWhatsAppLink($order);

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'whatsapp_link' => $waLink
        ]);
    }

    /**
     * Avança o status do pedido na sequência obrigatória
     */
    public function advanceStatus(Request $request, int $id)
    {
        $request->validate([
            'status_novo' => 'required|string',
            'observacao' => 'nullable|string|max:255',
        ]);

        try {
            $this->statusService->transitionTo(
                $id,
                $request->status_novo,
                Auth::guard('admin')->id(),
                $request->observacao
            );

            return back()->with('success', 'Status do pedido atualizado com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Confirmação manual de pagamento por PIX/Outro
     */
    public function confirmPayment(Request $request, int $id)
    {
        $request->validate([
            'observacao' => 'required|string|max:255',
            'custos' => 'nullable|array',
            'custos.*' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($id, $request) {
                // Se houver custos de dropshipping informados, atualiza os itens correspondentes
                if ($request->has('custos') && is_array($request->custos)) {
                    foreach ($request->custos as $itemId => $custoValue) {
                        DB::table('itens_pedido')
                            ->where('pedido_id', $id)
                            ->where('id', $itemId)
                            ->where('tipo_estoque_snapshot', 'dropshipping')
                            ->update([
                                'preco_custo_snapshot' => $custoValue
                            ]);
                    }
                }

                $this->statusService->confirmPaymentManually(
                    $id,
                    Auth::guard('admin')->id(),
                    $request->observacao
                );
            });

            return back()->with('success', 'Pagamento confirmado manualmente! Pedido avançado para Separação.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * WhatsApp manual com mensagem pré-formatada contendo o status ou rastreio
     */
    private function generateWhatsAppLink(Pedido $order): string
    {
        $whats = preg_replace('/[^0-9]/', '', $order->cliente->whatsapp ?? $order->cliente->telefone);
        if (empty($whats)) {
            return '#';
        }

        // Garante que o DDI 55 só seja adicionado se não estiver presente
        if (!str_starts_with($whats, '55') || strlen($whats) < 12) {
            $whats = '55' . $whats;
        }

        $mensagem = "Olá, {$order->cliente->nome_completo}! Seu pedido #{$order->id} na 90-Store ";

        if ($order->status === 'aguardando_pagamento') {
            $mensagem .= "foi recebido e está aguardando o pagamento do Pix.";
        } elseif ($order->status === 'em_separacao') {
            $mensagem .= "teve o pagamento confirmado e já está na fila de separação.";
        } elseif ($order->status === 'enviado') {
            $mensagem .= "acaba de ser postado! Código de rastreio: {$order->codigo_rastreio}. Acompanhe aqui: {$order->url_rastreio}";
        } elseif ($order->status === 'entregue') {
            $mensagem .= "foi entregue no seu endereço. Agradecemos a preferência!";
        } else {
            $mensagem .= "teve seu status atualizado para: " . strtoupper($order->status);
        }

        return "https://wa.me/{$whats}?text=" . urlencode($mensagem);
    }
}
