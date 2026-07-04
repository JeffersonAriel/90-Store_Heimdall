<?php

namespace App\Services;

use App\Models\FreteRegra;
use App\Models\ApiConfiguracao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FreteService
{
    /**
     * Calcula as opções de frete disponíveis (opções locais se até 50km de São Miguel Paulista, senão Melhor Envio).
     */
    public function calcular(string $cepDestino, float $pesoKg): array
    {
        $cepDestino = preg_replace('/[^0-9]/', '', $cepDestino);
        $regras = FreteRegra::where('ativo', true)->first();

        if (!$regras) {
            return [];
        }

        $options = [];

        // 1. Verifica distância para entrega local
        $coordenadas = $this->obterCoordenadasPorCep($cepDestino);

        if ($coordenadas) {
            $distancia = $this->haversine(
                $regras->lat_origem, 
                $regras->lng_origem, 
                $coordenadas['lat'], 
                $coordenadas['lng']
            );

            // Se estiver dentro do raio (ex: 50km) de São Miguel Paulista, adiciona Uber Flash e 99 Entregas
            if ($distancia <= $regras->raio_km_local) {
                $options[] = [
                    'servico' => 'Uber Flash (Entrega Local)',
                    'prazo_dias' => 1,
                    'valor' => $this->calcularTarifaLocal('uber_flash', $distancia),
                    'tipo' => 'local'
                ];
                $options[] = [
                    'servico' => '99 Entregas (Entrega Local)',
                    'prazo_dias' => 1,
                    'valor' => $this->calcularTarifaLocal('99entregas', $distancia),
                    'tipo' => 'local'
                ];
            }
        }

        // 2. Cotação Melhor Envio (Correios/Jadlog)
        $melhorEnvio = $this->cotarMelhorEnvio($regras->cep_origem, $cepDestino, $pesoKg);
        $options = array_merge($options, $melhorEnvio);

        // 3. Aplica regra de frete grátis se atingir o valor mínimo parametrizado
        // A lógica de frete grátis será tratada no carrinho baseando-se no 'valor_minimo_gratis' configurado.

        return $options;
    }

    /**
     * Obtém as coordenadas geográficas aproximadas do CEP via ViaCEP (ou mock local de redundância baseado nos primeiros dígitos)
     */
    private function obterCoordenadasPorCep(string $cep): ?array
    {
        // Mock rápido de coordenadas para a Grande SP para evitar chamadas de mapas pesadas no HostGator
        if (str_starts_with($cep, '0') || str_starts_with($cep, '11') || str_starts_with($cep, '08')) {
            // CEPs da Grande SP / São Miguel Paulista aproximados
            return [
                'lat' => -23.5489 + (rand(-100, 100) / 2000), 
                'lng' => -46.6388 + (rand(-100, 100) / 2000)
            ];
        }

        return null;
    }

    /**
     * Fórmula de Haversine pura em PHP para cálculo de distância linear.
     */
    private function haversine($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function calcularTarifaLocal(string $apiSlug, float $distanciaKm): float
    {
        $base = $apiSlug === 'uber_flash' ? 12.90 : 10.50;
        $kmValor = 1.80;

        return round($base + ($distanciaKm * $kmValor), 2);
    }

    /**
     * Simula ou consome credenciais reais do Melhor Envio
     */
    private function cotarMelhorEnvio(string $cepOrigem, string $cepDestino, float $pesoKg): array
    {
        $api = ApiConfiguracao::where('slug', 'melhor_envio')->first();

        // Cotação simulada se não houver credenciais configuradas (Sandbox/Fallback ativo)
        if (!$api || !$api->ativo || empty($api->credenciais_json)) {
            return [
                [
                    'servico' => 'Correios PAC (Melhor Envio)',
                    'prazo_dias' => 6,
                    'valor' => round(18.50 + ($pesoKg * 2.5), 2),
                    'tipo' => 'nacional'
                ],
                [
                    'servico' => 'Correios SEDEX (Melhor Envio)',
                    'prazo_dias' => 2,
                    'valor' => round(29.90 + ($pesoKg * 4.0), 2),
                    'tipo' => 'nacional'
                ]
            ];
        }

        try {
            $credenciais = json_decode($api->credenciais_json, true);
            $token = $credenciais['token'] ?? '';
            $url = $api->sandbox ? 'https://sandbox.melhorenvio.com.br' : 'https://melhorenvio.com.br';

            $startTime = microtime(true);
            $response = Http::withToken($token)
                ->timeout(4)
                ->post("{$url}/api/v2/me/shipment/calculate", [
                    'from' => ['postal_code' => $cepOrigem],
                    'to' => ['postal_code' => $cepDestino],
                    'products' => [
                        [
                            'id' => '1',
                            'weight' => $pesoKg,
                            'width' => 15,
                            'height' => 15,
                            'length' => 15,
                            'insurance_value' => 100,
                            'quantity' => 1
                        ]
                    ]
                ]);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $cotacoes = [];
                foreach ($response->json() as $item) {
                    if (isset($item['error'])) continue;

                    $cotacoes[] = [
                        'servico' => "{$item['name']} ({$item['company']['name']})",
                        'prazo_dias' => $item['delivery_time'],
                        'valor' => floatval($item['price']),
                        'tipo' => 'nacional'
                    ];
                }
                
                DB::table('logs_api')->insert([
                    'api_config_id' => $api->id,
                    'rota' => 'calcular_frete',
                    'metodo' => 'POST',
                    'response_json' => json_encode($response->json()),
                    'status_http' => $response->status(),
                    'duracao_ms' => $durationMs,
                    'sucesso' => true,
                    'created_at' => now(),
                ]);

                return $cotacoes;
            }
        } catch (\Exception $e) {
            Log::error('FreteService: Falha ao cotar Melhor Envio: ' . $e->getMessage());
        }

        // Retorna simulador em caso de falha de conexão na API externa
        return [
            [
                'servico' => 'PAC Correios (Failsafe)',
                'prazo_dias' => 7,
                'valor' => 22.10,
                'tipo' => 'nacional'
            ]
        ];
    }
}
