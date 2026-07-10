<?php

namespace App\Services;

use App\Models\ApiConfiguracao;
use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InfinitePayService
{
    protected $baseUrl = 'https://api.checkout.infinitepay.io';
    protected $handle;
    protected $config;

    public function __construct()
    {
        $this->config = ApiConfiguracao::where('slug', 'infinitepay')->first();
        
        // Prioriza a handle vinda do cadastro do banco de dados (credenciais_json)
        $handle = null;
        if ($this->config && $this->config->credenciais_json) {
            $creds = is_array($this->config->credenciais_json) 
                ? $this->config->credenciais_json 
                : json_decode($this->config->credenciais_json, true);
            $handle = $creds['handle'] ?? null;
        }

        // Fallback para a variável de ambiente
        $this->handle = $handle ?: env('INFINITEPAY_HANDLE');
    }

    /**
     * Gera o link de pagamento na InfinitePay
     */
    public function createPaymentLink(Pedido $pedido): array
    {
        $pedido->load(['cliente', 'endereco', 'itens.produto']);

        $cliente = $pedido->cliente;
        $endereco = $pedido->endereco;

        // Montagem do payload conforme especificações da InfinitePay
        // Docs: { "quantity": 1, "price": 123, "description": "..." }
        $items = [];
        foreach ($pedido->itens as $item) {
            $items[] = [
                'description' => $item->nome_snapshot,
                'quantity'    => (int) $item->quantidade,
                'price'       => (int) round($item->preco_venda_snapshot * 100), // centavos
            ];
        }

        // Adiciona frete como um item se houver
        if ($pedido->valor_frete > 0) {
            $items[] = [
                'description' => 'Frete',
                'quantity'    => 1,
                'price'       => (int) round($pedido->valor_frete * 100),
            ];
        }

        // Desconto do cupom se aplicável
        if ($pedido->desconto_cupom > 0) {
            $items[] = [
                'description' => 'Desconto Cupom',
                'quantity'    => 1,
                'price'       => -(int) round($pedido->desconto_cupom * 100),
            ];
        }

        if ($pedido->desconto_pontos > 0) {
            $items[] = [
                'description' => 'Desconto Pontos',
                'quantity'    => 1,
                'price'       => -(int) round($pedido->desconto_pontos * 100),
            ];
        }

        // Formata telefone com DDI +55 para pré-preencher no checkout InfinitePay
        $phoneRaw = preg_replace('/\D/', '', $cliente->telefone ?? $cliente->whatsapp ?? '');
        $phone = ($phoneRaw && strlen($phoneRaw) >= 10) ? '+55' . $phoneRaw : null;

        // Formatação do payload
        $payload = [
            'handle'    => $this->handle,
            'order_nsu' => 'PED' . str_pad($pedido->id, 8, '0', STR_PAD_LEFT),
            'items'     => $items,
            'redirect_url' => url('/pagamento/sucesso?order_id=' . $pedido->id),
            'webhook_url'  => url('/api/payments/infinitepay/webhook'),
            // Dados do cliente para pré-preencher a tela de contato
            'customer' => array_filter([
                'name'         => $cliente->nome_completo,
                'email'        => $cliente->email,
                'phone_number' => $phone,
            ]),
        ];

        Log::info('InfinitePay createPaymentLink payload', $payload);

        $startTime = microtime(true);
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/links", $payload);
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $data = $response->json();

            Log::info('InfinitePay response', ['status' => $response->status(), 'body' => $data]);

            if ($response->successful() && isset($data['url'])) {
                $this->registrarLogApi("links", 'POST', $payload, $data, $response->status(), $durationMs, true);
                return [
                    'success' => true,
                    'url' => $data['url'],
                    'payload' => $data
                ];
            }

            $this->registrarLogApi("links", 'POST', $payload, $data ?? null, $response->status(), $durationMs, false, 'Falha ao criar link de pagamento na InfinitePay.');
            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erro desconhecido da InfinitePay.'
            ];
        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $this->registrarLogApi("links", 'POST', $payload, null, 500, $durationMs, false, $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exceção na comunicação com a InfinitePay: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Consulta e valida o pagamento via payment_check na InfinitePay
     */
    public function checkPayment(string $orderNsu, string $transactionNsu, string $slug): array
    {
        $payload = [
            'handle' => $this->handle,
            'order_nsu' => $orderNsu,
            'transaction_nsu' => $transactionNsu,
            'slug' => $slug,
        ];

        $startTime = microtime(true);
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/payment_check", $payload);
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $data = $response->json();

            if ($response->successful()) {
                $this->registrarLogApi("payment_check", 'POST', $payload, $data, $response->status(), $durationMs, true);
                return [
                    'success' => true,
                    'paid' => $data['paid'] ?? false,
                    'data' => $data
                ];
            }

            $this->registrarLogApi("payment_check", 'POST', $payload, $data ?? null, $response->status(), $durationMs, false, 'Falha ao verificar pagamento na InfinitePay.');
            return [
                'success' => false,
                'paid' => false,
                'message' => $data['message'] ?? 'Erro desconhecido na verificação.'
            ];
        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $this->registrarLogApi("payment_check", 'POST', $payload, null, 500, $durationMs, false, $e->getMessage());
            return [
                'success' => false,
                'paid' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function registrarLogApi(string $rota, string $metodo, ?array $request, ?array $response, int $status, int $duration, bool $sucesso, ?string $erro = null)
    {
        try {
            DB::table('logs_api')->insert([
                'api_config_id' => $this->config ? $this->config->id : null,
                'rota'          => $rota,
                'metodo'        => $metodo,
                'request_json'  => $request ? json_encode($request) : null,
                'response_json' => $response ? json_encode($response) : null,
                'status_http'   => $status,
                'duracao_ms'    => $duration,
                'sucesso'       => $sucesso,
                'erro_mensagem' => $erro,
                'created_at'    => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('InfinitePayService: Falha ao gravar log da API: ' . $e->getMessage());
        }
    }
}
