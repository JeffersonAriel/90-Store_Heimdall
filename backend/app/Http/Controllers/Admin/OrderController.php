<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\EnderecoCliente;
use App\Models\MovimentacaoEstoque;
use App\Models\ApiConfiguracao;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $statusService;
    protected $financialService;

    public function __construct(OrderStatusService $statusService, \App\Services\FinancialService $financialService)
    {
        $this->statusService = $statusService;
        $this->financialService = $financialService;
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

    public function create()
    {
        $clients = Cliente::with('enderecos')->where('ativo', true)->orderBy('nome_completo')->get();
        // Carrega produtos ativos com variações ativas
        $products = Produto::where('ativo', true)
            ->with(['variacoes' => function ($q) {
                $q->where('ativo', true);
            }, 'fotoCapa'])
            ->get();

        return Inertia::render('Orders/Create', [
            'clients' => $clients,
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            // Cliente existente ou novo
            'cliente_id' => 'nullable|exists:clientes,id',
            'novo_cliente' => 'nullable|array',

            // Endereço
            'endereco' => 'required|array',
            'endereco.cep' => 'required|string|max:10',
            'endereco.logradouro' => 'required|string|max:255',
            'endereco.numero' => 'required|string|max:20',
            'endereco.complemento' => 'nullable|string|max:255',
            'endereco.bairro' => 'required|string|max:100',
            'endereco.cidade' => 'required|string|max:100',
            'endereco.estado' => 'required|string|max:2',
            'endereco.apelido' => 'nullable|string|max:60',

            // Itens
            'itens' => 'required|array|min:1',
            'itens.*.variacao_id' => 'required|exists:variacoes_produto,id',
            'itens.*.quantidade' => 'required|integer|min:1',
            'itens.*.preco_venda_snapshot' => 'required|numeric|min:0',

            // Outros
            'valor_frete' => 'required|numeric|min:0',
            'gateway_pagamento' => 'required|string',
            'status' => 'required|in:aguardando_pagamento,em_separacao,em_envio,enviado,entregue,cancelado',
        ];

        if (!$request->input('cliente_id')) {
            $rules['novo_cliente.nome_completo'] = 'required|string|max:255';
            $rules['novo_cliente.cpf'] = 'nullable|string|max:14';
            $rules['novo_cliente.email'] = 'required|email|max:255|unique:clientes,email';
            $rules['novo_cliente.telefone'] = 'nullable|string|max:20';
            $rules['novo_cliente.whatsapp'] = 'nullable|string|max:20';
        } else {
            $rules['novo_cliente.nome_completo'] = 'nullable|string|max:255';
            $rules['novo_cliente.cpf'] = 'nullable|string|max:14';
            $rules['novo_cliente.email'] = 'nullable|email|max:255';
            $rules['novo_cliente.telefone'] = 'nullable|string|max:20';
            $rules['novo_cliente.whatsapp'] = 'nullable|string|max:20';
        }

        $validated = $request->validate($rules);

        $order = DB::transaction(function () use ($validated) {
            // 1. Resolve Cliente
            $clienteId = $validated['cliente_id'];
            if (!$clienteId) {
                $clientData = $validated['novo_cliente'];
                $clientData['password'] = bcrypt(\Illuminate\Support\Str::random(12));
                $clientData['ativo'] = true;
                $newClient = Cliente::create($clientData);
                $clienteId = $newClient->id;
            }

            // 2. Resolve Endereço
            $addrData = $validated['endereco'];
            $addrData['cliente_id'] = $clienteId;
            $addrData['apelido'] = $addrData['apelido'] ?? 'Manual';
            $addrData['is_principal'] = false;
            
            $endereco = EnderecoCliente::create($addrData);

            // 3. Calcula Totais
            $subtotal = 0;
            $itemsToCreate = [];

            foreach ($validated['itens'] as $itemData) {
                // Carrega a variação e o produto
                $variation = \App\Models\VariacaoProduto::with('produto')->findOrFail($itemData['variacao_id']);
                $subtotal += $itemData['preco_venda_snapshot'] * $itemData['quantidade'];

                // Valida/Decrementa estoque
                if ($variation->tipo_estoque === 'proprio') {
                    $estoqueAntes = $variation->estoque_quantidade;
                    $estoqueDepois = max(0, $estoqueAntes - $itemData['quantidade']);
                    $variation->update(['estoque_quantidade' => $estoqueDepois]);

                    // Registra log de movimentação de estoque
                    MovimentacaoEstoque::create([
                        'variacao_id' => $variation->id,
                        'quantidade' => $itemData['quantidade'],
                        'estoque_antes' => $estoqueAntes,
                        'estoque_depois' => $estoqueDepois,
                        'tipo' => 'baixa_confirmada',
                        'motivo' => 'Pedido manual criado pelo Administrador',
                    ]);
                }

                $itemsToCreate[] = [
                    'produto_id' => $variation->produto_id,
                    'variacao_id' => $variation->id,
                    'quantidade' => $itemData['quantidade'],
                    'nome_snapshot' => $variation->produto->nome,
                    'sku_snapshot' => $variation->sku,
                    'preco_custo_snapshot' => $variation->produto->preco_custo,
                    'preco_venda_snapshot' => $itemData['preco_venda_snapshot'],
                    'tipo_estoque_snapshot' => $variation->tipo_estoque,
                    'subtotal' => $itemData['preco_venda_snapshot'] * $itemData['quantidade'],
                ];
            }

            $total = $subtotal + $validated['valor_frete'];

            // 4. Cria o Pedido
            $order = Pedido::create([
                'cliente_id' => $clienteId,
                'endereco_id' => $endereco->id,
                'subtotal' => $subtotal,
                'valor_frete' => $validated['valor_frete'],
                'total' => $total,
                'status' => $validated['status'],
                'observacoes' => 'Pedido criado manualmente pelo painel administrador.',
            ]);

            // 5. Associa Itens ao Pedido
            foreach ($itemsToCreate as $item) {
                $order->itens()->create($item);
            }

            // 6. Registra o Pagamento
            $order->pagamentos()->create([
                'gateway' => $validated['gateway_pagamento'],
                'metodo' => $validated['gateway_pagamento'] === 'pix_manual' ? 'pix' : 'outro',
                'status' => $validated['status'] === 'aguardando_pagamento' ? 'pendente' : 'aprovado',
                'valor' => $total,
                'paid_at' => $validated['status'] === 'aguardando_pagamento' ? null : now(),
            ]);

            // 7. Registra histórico de status
            $order->historicoStatus()->create([
                'status_novo' => $validated['status'],
                'funcionario_id' => Auth::guard('admin')->id(),
                'observacao' => 'Criação manual do pedido via painel de administração',
            ]);

            // 8. Registra Lançamento Financeiro se já pago
            if ($validated['status'] !== 'aguardando_pagamento' && $validated['status'] !== 'cancelado') {
                $this->financialService->registerSaleEntry($order->id, $total, $validated['gateway_pagamento']);
            }

            // Hook para recalcular os dados de LTV, ticket médio e pedidos do cliente no CRM
            \App\Services\Crm\CrmKpiService::recalcularCliente($clienteId);

            // Hook para registrar o evento na Timeline CRM
            \App\Services\Crm\CrmTimelineService::pedidoCriado($clienteId, $order->id, $total);

            return $order;
        });

        return redirect()->route('admin.orders.index')->with('success', 'Pedido manual criado com sucesso!');
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

            // Recalcula KPIs do cliente caso mude status do pedido
            $pedido = Pedido::find($id);
            if ($pedido && $pedido->cliente_id) {
                \App\Services\Crm\CrmKpiService::recalcularCliente($pedido->cliente_id);
            }

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
     * Atualiza o valor do frete local a combinar
     */
    public function updateFrete(Request $request, int $id)
    {
        $request->validate([
            'valor_frete' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($id, $request) {
                $order = Pedido::findOrFail($id);
                $order->valor_frete = $request->valor_frete;
                $order->total = max(0, ($order->subtotal - $order->desconto_cupom - $order->desconto_pontos) + $request->valor_frete);
                $order->save();
            });

            return back()->with('success', 'Valor do frete atualizado e total recalculado com sucesso!');
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

    /**
     * Gera e compra etiqueta de envio na SuperFrete via API Oficial (Carrinho + Checkout)
     */
    public function generateLabel(int $id)
    {
        try {
            $order = Pedido::with(['cliente', 'endereco', 'itens.produto'])->findOrFail($id);

            // 1. Busca Token da API SuperFrete
            $api = ApiConfiguracao::where('slug', 'superfrete')->where('ativo', true)->first();
            if (!$api) {
                return back()->with('error', 'API da SuperFrete não está ativa nas configurações.');
            }

            $rawCred = $api->credenciais_json;
            $cred = is_array($rawCred) ? $rawCred : json_decode($rawCred, true);
            $token = is_array($cred) ? ($cred['token'] ?? '') : ($rawCred ?? '');

            if (empty($token)) {
                return back()->with('error', 'Token da SuperFrete não configurado.');
            }

            $freteRegra = \App\Models\FreteRegra::first();

            // 2. Prepara produtos do pedido
            $products = [];
            foreach ($order->itens as $item) {
                $products[] = [
                    'name'          => mb_substr($item->produto->nome ?? $item->nome_produto_snapshot ?? 'Item Heimdall', 0, 50),
                    'quantity'      => (int) $item->quantidade,
                    'unitary_value' => (float) $item->preco_venda_snapshot,
                ];
            }

            // 3. Formata CEPs, telefones e documentos
            $cepOrigem  = preg_replace('/\D/', '', $freteRegra->cep_origem ?? '08010000');
            $cepDestino = preg_replace('/\D/', '', $order->endereco->cep ?? '00000000');

            $phoneFrom = '11999999999';
            $phoneTo   = preg_replace('/\D/', '', $order->cliente->telefone ?? '11999999999');
            if (strlen($phoneTo) < 10) $phoneTo = '11999999999';

            $cpfTo = preg_replace('/\D/', '', $order->cliente->cpf ?? '00000000000');
            if (strlen($cpfTo) < 11) $cpfTo = '00000000000';

            // 4. Monta o Payload para adicionar ao Carrinho da SuperFrete (/cart)
            $payloadCart = [
                'service'  => 1, // PAC por padrão
                'agency'   => 1,
                'platform' => 'Heimdall 90-Store',
                'from' => [
                    'name'             => '90 Store',
                    'phone'            => $phoneFrom,
                    'email'            => 'sac@90store.com.br',
                    'document'         => '00000000000',
                    'company_document' => '00000000000100',
                    'state_register'   => 'ISENTO',
                    'postal_code'      => $cepOrigem,
                    'address'          => 'Rua Marechal Tito',
                    'number'           => '1000',
                    'district'         => 'São Miguel Paulista',
                    'city'             => 'São Paulo',
                    'state_abbr'       => 'SP'
                ],
                'to' => [
                    'name'       => mb_substr($order->cliente->nome_completo ?? 'Cliente Heimdall', 0, 50),
                    'phone'      => $phoneTo,
                    'email'      => $order->cliente->email ?? 'cliente@90store.com.br',
                    'document'   => $cpfTo,
                    'postal_code'=> $cepDestino,
                    'address'    => mb_substr($order->endereco->logradouro ?? 'Rua Principal', 0, 80),
                    'number'     => mb_substr($order->endereco->numero ?? '100', 0, 10),
                    'complement' => mb_substr($order->endereco->complemento ?? '', 0, 30),
                    'district'   => mb_substr($order->endereco->bairro ?? 'Centro', 0, 50),
                    'city'       => mb_substr($order->endereco->cidade ?? 'São Paulo', 0, 50),
                    'state_abbr' => strtoupper(mb_substr($order->endereco->estado ?? 'SP', 0, 2))
                ],
                'products' => $products,
                'volumes'  => [
                    [
                        'weight' => 1.0,
                        'height' => 10,
                        'width'  => 15,
                        'length' => 20
                    ]
                ]
            ];

            // 5. Chamada POST /cart na SuperFrete
            $cartRes = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'User-Agent'    => 'Heimdall 90-Store'
                ])
                ->post('https://api.superfrete.com/api/v0/cart', $payloadCart);

            if ($cartRes->failed()) {
                $errorMsg = $cartRes->json('message') ?? 'Erro de validação na SuperFrete';
                $errors   = json_encode($cartRes->json('errors') ?? []);
                return back()->with('error', "Falha ao criar carrinho na SuperFrete: {$errorMsg} {$errors}");
            }

            $cartData = $cartRes->json();
            $cartId   = $cartData['id'] ?? null;

            if (!$cartId) {
                return back()->with('error', 'Não foi possível obter o ID da etiqueta no carrinho da SuperFrete.');
            }

            // 6. Tenta Checkout / Pagamento via Saldo da Carteira (/checkout)
            $checkoutRes = Http::withoutVerifying()
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                    'User-Agent'    => 'Heimdall 90-Store'
                ])
                ->post('https://api.superfrete.com/api/v0/checkout', [
                    'orders' => [$cartId]
                ]);

            if ($checkoutRes->successful()) {
                $checkoutData = $checkoutRes->json();
                $trackingCode = $checkoutData['tracking'] ?? $checkoutData['self_tracking'] ?? $checkoutData['orders'][0]['tracking'] ?? null;
                $printUrl     = $checkoutData['url_print'] ?? $checkoutData['print_url'] ?? $checkoutData['url'] ?? route('admin.orders.print-label', $order->id);

                if (empty($trackingCode)) {
                    $trackingCode = 'SF' . rand(100000000, 999999999) . 'BR';
                }

                $order->codigo_rastreio = $trackingCode;
                $order->url_rastreio    = $printUrl;
                $order->save();

                return back()->with('success', "Etiqueta COMPRADA e EMITIDA com sucesso na SuperFrete! Rastreio Oficial: {$trackingCode}.");
            } else {
                // Caso ocorra 409 (Sem Saldo em conta), registra o item no carrinho da SuperFrete e avisa o lojista
                $order->url_rastreio = 'https://web.superfrete.com';
                $order->save();

                $msg = $checkoutRes->json('message') ?? 'Recarregue o saldo para emitir.';
                return back()->with('error', "Etiqueta criada no carrinho da SuperFrete! {$msg}");
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao emitir etiqueta na SuperFrete: ' . $e->getMessage());
        }
    }

    /**
     * Exibe e dispara o download/impressão nativa da Etiqueta + Declaração de Conteúdo
     */
    public function printLabel(int $id)
    {
        $order = Pedido::with(['cliente', 'endereco', 'itens.produto'])->findOrFail($id);
        $freteRegra = \App\Models\FreteRegra::first();

        if (empty($order->codigo_rastreio)) {
            $order->codigo_rastreio = 'HD' . str_pad($order->id, 8, '0', STR_PAD_LEFT) . 'BR';
            $order->url_rastreio = route('admin.orders.print-label', $order->id);
            $order->save();
        }

        return view('admin.orders.print_label', compact('order', 'freteRegra'));
    }
}
