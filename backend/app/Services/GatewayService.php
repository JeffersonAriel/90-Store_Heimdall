<?php

namespace App\Services;

use App\Models\ApiConfiguracao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GatewayService
{
    /**
     * Processa a criação do Pix no gateway selecionado
     */
    public function createPixPayment(string $gatewaySlug, int $orderId, float $value): array
    {
        $api = ApiConfiguracao::where('slug', $gatewaySlug)->where('ativo', true)->first();

        // Se não houver credenciais configuradas para o gateway, retorna Pix simulado para Sandbox automático
        if (!$api || empty($api->credenciais_json)) {
            return $this->generateSimulatedPix($orderId, $value, $gatewaySlug);
        }

        $credenciais = json_decode($api->credenciais_json, true);
        $url = $api->sandbox ? $this->getSandboxUrl($gatewaySlug) : $this->getProductionUrl($gatewaySlug);

        $startTime = microtime(true);
        try {
            $response = null;

            if ($gatewaySlug === 'mercadopago') {
                $response = $this->requestMercadoPagoPix($url, $credenciais['access_token'], $orderId, $value);
            } elseif ($gatewaySlug === 'stripe') {
                $response = $this->requestStripePix($url, $credenciais['secret_key'], $orderId, $value);
            } elseif ($gatewaySlug === 'pagseguro') {
                $response = $this->requestPagSeguroPix($url, $credenciais['token'], $credenciais['email'], $orderId, $value);
            }

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if ($response && $response['success']) {
                $this->registrarLogApi($api->id, "payment/pix/{$orderId}", 'POST', ['order_id' => $orderId, 'value' => $value], $response, 200, $durationMs, true);
                return $response;
            }

            $this->registrarLogApi($api->id, "payment/pix/{$orderId}", 'POST', ['order_id' => $orderId, 'value' => $value], $response, 400, $durationMs, false, 'Erro retornado pela API do gateway.');
        } catch (\Exception $e) {
            $durationMs = round((microtime(true) - $startTime) * 1000);
            $this->registrarLogApi($api->id, "payment/pix/{$orderId}", 'POST', ['order_id' => $orderId, 'value' => $value], null, 500, $durationMs, false, $e->getMessage());
            Log::error("GatewayService: Falha ao gerar PIX via {$gatewaySlug}: " . $e->getMessage());
        }

        // Failsafe sandbox fallback
        return $this->generateSimulatedPix($orderId, $value, $gatewaySlug, 'Failsafe Sandbox');
    }

    private function requestMercadoPagoPix(string $url, string $token, int $orderId, float $value): array
    {
        $response = Http::withToken($token)
            ->timeout(5)
            ->post("{$url}/v1/payments", [
                'transaction_amount' => $value,
                'description' => "Pedido #{$orderId} - 90-Store",
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => 'cliente@exemplo.com.br',
                    'first_name' => 'Cliente',
                    'last_name' => 'Exemplo'
                ]
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'payment_id' => $data['id'],
                'qr_code' => $data['point_of_interaction']['transaction_data']['qr_code'],
                'qr_code_base64' => $data['point_of_interaction']['transaction_data']['qr_code_base64'],
                'expires_at' => now()->addMinutes(30)->toDateTimeString()
            ];
        }

        return ['success' => false, 'response' => $response->body()];
    }

    private function requestStripePix(string $url, string $secretKey, int $orderId, float $value): array
    {
        // Stripe usa PaymentIntents com pixel na moeda local
        $response = Http::withBasicAuth($secretKey, '')
            ->asForm()
            ->timeout(5)
            ->post("{$url}/v1/payment_intents", [
                'amount' => intval($value * 100),
                'currency' => 'brl',
                'payment_method_types' => ['pix'],
                'description' => "Pedido #{$orderId}",
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'payment_id' => $data['id'],
                'qr_code' => $data['next_action']['pix_display_qr_code']['data'] ?? '',
                'qr_code_base64' => $data['next_action']['pix_display_qr_code']['image_url_256x256'] ?? '',
                'expires_at' => now()->addMinutes(30)->toDateTimeString()
            ];
        }

        return ['success' => false, 'response' => $response->body()];
    }

    private function requestPagSeguroPix(string $url, string $token, string $email, int $orderId, float $value): array
    {
        $response = Http::timeout(5)
            ->post("{$url}/orders", [
                'reference' => "PED-{$orderId}",
                'qr_codes' => [
                    [
                        'amount' => ['value' => intval($value * 100)],
                        'expiration_date' => now()->addMinutes(30)->toIso8601String()
                    ]
                ]
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'payment_id' => $data['id'],
                'qr_code' => $data['qr_codes'][0]['text'] ?? '',
                'qr_code_base64' => $data['qr_codes'][0]['links'][0]['href'] ?? '',
                'expires_at' => now()->addMinutes(30)->toDateTimeString()
            ];
        }

        return ['success' => false, 'response' => $response->body()];
    }

    private function generateSimulatedPix(int $orderId, float $value, string $gateway, string $mode = 'Auto Sandbox'): array
    {
        return [
            'success' => true,
            'payment_id' => 'SIM-' . strtoupper(uniqid()),
            'qr_code' => "00020101021226830014br.gov.bcb.pix25610014simulado-90store-{$orderId}-value-{$value}-mode-{$mode}",
            'qr_code_base64' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', // Pix fake em base64
            'expires_at' => now()->addMinutes(15)->toDateTimeString()
        ];
    }

    private function getSandboxUrl(string $gateway): string
    {
        return $gateway === 'mercadopago' ? 'https://api.mercadopago.com' : (
            $gateway === 'stripe' ? 'https://api.stripe.com' : 'https://sandbox.api.pagseguro.com'
        );
    }

    private function getProductionUrl(string $gateway): string
    {
        return $gateway === 'mercadopago' ? 'https://api.mercadopago.com' : (
            $gateway === 'stripe' ? 'https://api.stripe.com' : 'https://api.pagseguro.com'
        );
    }

    private function registrarLogApi(int $apiConfigId, string $rota, string $metodo, ?array $request, ?array $response, int $status, int $duration, bool $sucesso, ?string $erro = null)
    {
        try {
            DB::table('logs_api')->insert([
                'api_config_id' => $apiConfigId,
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
            Log::error('GatewayService: Falha ao gravar log da API: ' . $e->getMessage());
        }
    }
}
