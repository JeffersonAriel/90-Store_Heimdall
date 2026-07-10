<?php

namespace App\Services;

use App\Models\ApiConfiguracao;
use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InfinitePayService
{
    protected string $baseUrl = 'https://api.checkout.infinitepay.io';
    protected ?string $handle;
    protected ?ApiConfiguracao $config;

    public function __construct()
    {
        $this->config = ApiConfiguracao::where('slug', 'infinitepay')->first();

        // Prioriza a handle vinda do banco de dados (credenciais_json criptografado)
        $handle = null;
        if ($this->config && $this->config->credenciais_json) {
            $creds = is_array($this->config->credenciais_json)
                ? $this->config->credenciais_json
                : json_decode($this->config->credenciais_json, true);
            $handle = $creds['handle'] ?? null;
        }

        // Fallback para variável de ambiente
        $this->handle = $handle ?: env('INFINITEPAY_HANDLE');
    }

    // ────────────────────────────────────────────────────────────────────────────
    // [5] Valida se handle está configurada
    // ────────────────────────────────────────────────────────────────────────────
    private function validarHandle(): ?array
    {
        if (empty($this->handle)) {
            Log::error('InfinitePayService: Handle não configurada.');
            return [
                'success' => false,
                'message' => 'Handle da InfinitePay não configurada. Configure a InfiniteTag no painel de Segurança.',
            ];
        }
        return null;
    }

    // ────────────────────────────────────────────────────────────────────────────
    // [3] Normaliza telefone para +5511999999999
    //     Aceita: 11999999999 | 5511999999999 | +5511999999999
    // ────────────────────────────────────────────────────────────────────────────
    private function normalizarTelefone(?string $raw): ?string
    {
        if (empty($raw)) return null;

        $digits = preg_replace('/\D/', '', $raw);

        // Remove DDI 55 duplicado: 5511... com 13 dígitos → mantém apenas 11...
        if (strlen($digits) === 13 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2); // vira 11 dígitos (DDD + número)
        }

        // Aceita 10 ou 11 dígitos (com ou sem 9º dígito)
        if (strlen($digits) >= 10 && strlen($digits) <= 11) {
            return '+55' . $digits;
        }

        Log::warning('InfinitePayService: telefone inválido ignorado.', ['raw' => $raw]);
        return null;
    }

    /**
     * Gera o link de pagamento na InfinitePay
     */
    public function createPaymentLink(Pedido $pedido): array
    {
        // [5] Validação da handle
        if ($err = $this->validarHandle()) return $err;

        $pedido->load(['cliente', 'endereco', 'itens.produto']);

        $cliente  = $pedido->cliente;
        $endereco = $pedido->endereco;

        // ── [6] Monta itens — somente com qty > 0, price > 0 e description ──
        // [4] Descontos NÃO são enviados como itens negativos.
        //     O valor líquido já é calculado pelo pedido (subtotal - desconto).
        //     O frete só é adicionado se tiver valor positivo.
        $items = [];

        foreach ($pedido->itens as $item) {
            $preco = (int) round($item->preco_venda_snapshot * 100);
            $qty   = (int) $item->quantidade;
            $desc  = trim($item->nome_snapshot ?? '');

            if ($preco <= 0 || $qty <= 0 || $desc === '') {
                Log::warning('InfinitePayService: item ignorado por dados inválidos.', [
                    'variacao_id' => $item->variacao_id,
                    'preco'       => $preco,
                    'qty'         => $qty,
                    'desc'        => $desc,
                ]);
                continue;
            }

            $items[] = [
                'description' => $desc,
                'quantity'    => $qty,
                'price'       => $preco,
            ];
        }

        // Frete como item positivo (quando > 0)
        if ($pedido->valor_frete > 0) {
            $items[] = [
                'description' => 'Frete',
                'quantity'    => 1,
                'price'       => (int) round($pedido->valor_frete * 100),
            ];
        }

        // [6] Garante pelo menos 1 item válido
        if (empty($items)) {
            Log::error('InfinitePayService: nenhum item válido para criar link.', ['pedido_id' => $pedido->id]);
            return [
                'success' => false,
                'message' => 'Nenhum item válido no pedido para gerar link de pagamento.',
            ];
        }

        // ── Payload obrigatório ───────────────────────────────────────────────
        $payload = [
            'handle'       => $this->handle,
            'order_nsu'    => 'PED' . str_pad($pedido->id, 8, '0', STR_PAD_LEFT),
            'redirect_url' => url('/pagamento/sucesso?order_id=' . $pedido->id),
            'webhook_url'  => url('/api/payments/infinitepay/webhook'),
            'items'        => $items,
        ];

        // ── [7] customer — opcional, somente quando name e email estão presentes
        $customerName  = trim($cliente->nome_completo ?? '');
        $customerEmail = trim($cliente->email ?? '');

        if ($customerName !== '' && $customerEmail !== '') {
            $customer = [
                'name'  => $customerName,
                'email' => $customerEmail,
            ];

            // [3] Telefone: opcional dentro de customer
            $phone = $this->normalizarTelefone($cliente->telefone ?? $cliente->whatsapp ?? null);
            if ($phone !== null) {
                $customer['phone_number'] = $phone;
            }

            $payload['customer'] = $customer;
        }

        // ── [8] address — opcional, somente quando CEP e número presentes ────
        $cepLimpo = preg_replace('/\D/', '', $endereco->cep ?? '');
        $numero   = trim((string) ($endereco->numero ?? ''));

        if ($cepLimpo !== '' && $numero !== '') {
            $address = [
                'cep'    => $cepLimpo,
                'number' => $numero,
            ];

            $complement = trim($endereco->complemento ?? '');
            if ($complement !== '') {
                $address['complement'] = $complement;
            }

            $payload['address'] = $address;
        }

        // ── [9] Log do payload enviado ────────────────────────────────────────
        Log::info('InfinitePayService [createPaymentLink] payload enviado', [
            'pedido_id' => $pedido->id,
            'payload'   => $payload,
        ]);

        // ── [2] Requisição HTTP com tratamento de timeout e erros ─────────────
        $startTime = microtime(true);
        try {
            // [10] Timeout de 10 segundos
            $response  = Http::timeout(10)->post("{$this->baseUrl}/links", $payload);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $data       = $response->json();

            // [9] Log completo da resposta
            Log::info('InfinitePayService [createPaymentLink] resposta recebida', [
                'pedido_id'   => $pedido->id,
                'status_http' => $response->status(),
                'duracao_ms'  => $durationMs,
                'body'        => $data,
            ]);

            // [2] Sucesso: espera $data['url']
            if ($response->successful() && isset($data['url'])) {
                $this->registrarLogApi('links', 'POST', $payload, $data, $response->status(), $durationMs, true);
                return [
                    'success' => true,
                    'url'     => $data['url'],
                    'payload' => $data,
                ];
            }

            // [2] Falha da API: registra erro detalhado
            $erroMsg = $data['message'] ?? $data['error'] ?? json_encode($data) ?? 'Erro desconhecido da InfinitePay.';
            Log::error('InfinitePayService [createPaymentLink] falha na API', [
                'pedido_id'   => $pedido->id,
                'status_http' => $response->status(),
                'erro'        => $erroMsg,
                'body'        => $data,
            ]);

            $this->registrarLogApi('links', 'POST', $payload, $data, $response->status(), $durationMs, false, $erroMsg);
            return [
                'success' => false,
                'message' => 'Falha ao criar link InfinitePay: ' . $erroMsg,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // [10] Timeout ou conexão recusada
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('InfinitePayService [createPaymentLink] timeout/conexão', [
                'pedido_id'  => $pedido->id,
                'duracao_ms' => $durationMs,
                'exception'  => $e->getMessage(),
            ]);
            $this->registrarLogApi('links', 'POST', $payload, null, 0, $durationMs, false, 'Timeout: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Não foi possível conectar à InfinitePay. Tente novamente em instantes.',
            ];
        } catch (\Exception $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('InfinitePayService [createPaymentLink] exception', [
                'pedido_id'  => $pedido->id,
                'duracao_ms' => $durationMs,
                'exception'  => $e->getMessage(),
            ]);
            $this->registrarLogApi('links', 'POST', $payload, null, 500, $durationMs, false, $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro inesperado na comunicação com a InfinitePay.',
            ];
        }
    }

    // ────────────────────────────────────────────────────────────────────────────
    // [11] Consulta pagamento via payment_check
    //      NÃO marca como pago — quem decide é o caller (WebhookController)
    // ────────────────────────────────────────────────────────────────────────────
    public function checkPayment(string $orderNsu, string $transactionNsu, string $slug): array
    {
        // [5] Validação da handle
        if ($err = $this->validarHandle()) return array_merge($err, ['paid' => false]);

        $payload = [
            'handle'          => $this->handle,
            'order_nsu'       => $orderNsu,
            'transaction_nsu' => $transactionNsu,
            'slug'            => $slug,
        ];

        Log::info('InfinitePayService [checkPayment] payload enviado', ['payload' => $payload]);

        $startTime = microtime(true);
        try {
            $response   = Http::timeout(10)->post("{$this->baseUrl}/payment_check", $payload);
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $data       = $response->json();

            // [9] Log completo
            Log::info('InfinitePayService [checkPayment] resposta recebida', [
                'status_http' => $response->status(),
                'duracao_ms'  => $durationMs,
                'body'        => $data,
            ]);

            if ($response->successful()) {
                $this->registrarLogApi('payment_check', 'POST', $payload, $data, $response->status(), $durationMs, true);
                return [
                    'success'        => true,
                    'paid'           => (bool) ($data['paid'] ?? false),
                    'amount'         => $data['amount'] ?? null,
                    'paid_amount'    => $data['paid_amount'] ?? null,
                    'installments'   => $data['installments'] ?? null,
                    'capture_method' => $data['capture_method'] ?? null,
                    'data'           => $data,
                ];
            }

            $erroMsg = $data['message'] ?? $data['error'] ?? 'Erro desconhecido na verificação.';
            Log::error('InfinitePayService [checkPayment] falha na API', [
                'status_http' => $response->status(),
                'erro'        => $erroMsg,
                'body'        => $data,
            ]);

            $this->registrarLogApi('payment_check', 'POST', $payload, $data, $response->status(), $durationMs, false, $erroMsg);
            return [
                'success' => false,
                'paid'    => false,
                'message' => $erroMsg,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('InfinitePayService [checkPayment] timeout/conexão', ['exception' => $e->getMessage()]);
            $this->registrarLogApi('payment_check', 'POST', $payload, null, 0, $durationMs, false, 'Timeout: ' . $e->getMessage());
            return ['success' => false, 'paid' => false, 'message' => 'Timeout ao verificar pagamento na InfinitePay.'];
        } catch (\Exception $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            Log::error('InfinitePayService [checkPayment] exception', ['exception' => $e->getMessage()]);
            $this->registrarLogApi('payment_check', 'POST', $payload, null, 500, $durationMs, false, $e->getMessage());
            return ['success' => false, 'paid' => false, 'message' => 'Erro inesperado ao verificar pagamento.'];
        }
    }

    // ────────────────────────────────────────────────────────────────────────────
    // [9] Persiste log da chamada na tabela logs_api
    // ────────────────────────────────────────────────────────────────────────────
    private function registrarLogApi(
        string  $rota,
        string  $metodo,
        ?array  $request,
        ?array  $response,
        int     $status,
        int     $duration,
        bool    $sucesso,
        ?string $erro = null
    ): void {
        try {
            DB::table('logs_api')->insert([
                'api_config_id' => $this->config?->id,
                'rota'          => $rota,
                'metodo'        => $metodo,
                'request_json'  => $request  ? json_encode($request)  : null,
                'response_json' => $response ? json_encode($response) : null,
                'status_http'   => $status,
                'duracao_ms'    => $duration,
                'sucesso'       => $sucesso,
                'erro_mensagem' => $erro,
                'created_at'    => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('InfinitePayService: falha ao gravar log_api — ' . $e->getMessage());
        }
    }
}
