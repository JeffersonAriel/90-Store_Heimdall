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
        $this->handle = env('INFINITEPAY_HANDLE');
        $this->config = ApiConfiguracao::where('slug', 'infinitepay')->first();
    }

    /**
     * Gera o link de pagamento na InfinitePay
     */
    public function createPaymentLink(Pedido $pedido): array
    {
        $pedido->load(['cliente', 'endereco', 'itens.produto']);

        $cliente = $pedido->cliente;
        $endereco = $pedido->endereco;

        // Montagem do payload conforme especificações
        $items = [];
        foreach ($pedido->itens as $item) {
            $items[] = [
                'name' => $item->nome_snapshot,
                'sku' => $item->sku_snapshot,
                'quantity' => (int) $item->quantidade,
                'amount' => (int) round($item->preco_venda_snapshot * 100), // Preço unitário em centavos
            ];
        }

        // Adiciona frete como um item se houver
        if ($pedido->valor_frete > 0) {
            $items[] = [
                'name' => 'Frete',
                'sku' => 'FRETE',
                'quantity' => 1,
                'amount' => (int) round($pedido->valor_frete * 100),
            ];
        }

        // Desconto do cupom se aplicável (enviado com sinal negativo)
        if ($pedido->desconto_cupom > 0) {
            $items[] = [
                'name' => 'Desconto Cupom',
                'sku' => 'DESCONTO',
                'quantity' => 1,
                'amount' => -(int) round($pedido->desconto_cupom * 100),
            ];
        }

        if ($pedido->desconto_pontos > 0) {
            $items[] = [
                'name' => 'Desconto Pontos',
                'sku' => 'DESCONTO_PONTOS',
                'quantity' => 1,
                'amount' => -(int) round($pedido->desconto_pontos * 100),
            ];
        }

        // Limpar telefone (apenas números)
        $phone = preg_replace('/\D/', '', $cliente->telefone ?? $cliente->whatsapp ?? '');

        // Formatação do payload
        $payload = [
            'handle' => $this->handle,
            'order_nsu' => 'PED' . str_pad($pedido->id, 8, '0', STR_PAD_LEFT),
            'items' => $items,
            'customer' => [
                'name' => $cliente->nome_completo,
                'email' => $cliente->email,
                'phone' => $phone,
                'document' => preg_replace('/\D/', '', $cliente->cpf ?? ''),
            ],
            'address' => [
                'cep' => preg_replace('/\D/', '', $endereco->cep),
                'street' => $endereco->logradouro,
                'number' => $endereco->numero,
                'complement' => $endereco->complemento ?? '',
                'neighborhood' => $endereco->bairro,
                'city' => $endereco->cidade,
                'state' => $endereco->estado,
            ],
            'redirect_url' => url('/pagamento/sucesso?order_id=' . $pedido->id),
            'webhook_url' => url('/api/payments/infinitepay/webhook'),
        ];

        $startTime = microtime(true);
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/links", $payload);
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $data = $response->json();

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
