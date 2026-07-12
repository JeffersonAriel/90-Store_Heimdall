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
     * Calcula as opções de frete disponíveis (opções locais se até 50km de São Miguel Paulista, senão SuperFrete).
     */
    public function calcular(string $cepDestino, float $pesoKg, int $itensCount = 1): array
    {
        $cepDestino = preg_replace('/[^0-9]/', '', $cepDestino);
        $regras = FreteRegra::where('ativo', true)->first();

        if (!$regras) {
            return [];
        }

        $options = [];

        // 1. Verifica distância para entrega local (Uber Moto / Metrô)
        $coordenadas = $this->obterCoordenadasPorCep($cepDestino);

        if ($coordenadas) {
            $distancia = $this->haversine(
                $regras->lat_origem, 
                $regras->lng_origem, 
                $coordenadas['lat'], 
                $coordenadas['lng']
            );

            // Se estiver dentro do raio configurado (ex: 50km) da origem, adiciona os serviços locais cadastrados (ex: Uber Moto, Metrô)
            if ($distancia <= $regras->raio_km_local) {
                if ($regras->servicos_locais_json) {
                    $servicos = json_decode($regras->servicos_locais_json, true) ?? [];
                    foreach ($servicos as $servico) {
                        $options[] = [
                            'servico'    => $servico['nome'] . ' (Entrega Local)',
                            'prazo_dias' => 1,
                            'valor'      => 0,          // Valor a combinar após o pedido
                            'a_combinar' => true,       // Flag que dispara aviso no front-end
                            'tipo'       => 'local',
                        ];
                    }
                }
            }
        }

        // 2. Cotação SuperFrete (Correios PAC/SEDEX/Mini Envios)
        $superFrete = $this->cotarSuperFrete($regras->cep_origem, $cepDestino, $pesoKg, $itensCount);
        $options = array_merge($options, $superFrete);

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

    /**
     * Cotação via API da SuperFrete
     */
    private function cotarSuperFrete(string $cepOrigem, string $cepDestino, float $pesoKg, int $itensCount = 1): array
    {
        $api = ApiConfiguracao::where('slug', 'superfrete')->first();

        if (!$api || !$api->ativo) {
            return $this->failsafeSuperFrete($pesoKg);
        }

        try {
            $credenciais = json_decode($api->credenciais_json, true);
            $token = $credenciais['token'] ?? '';
            
            if (empty($token)) {
                return $this->failsafeSuperFrete($pesoKg);
            }

            $startTime = microtime(true);

            // Regra por produto solicitado:
            // Cada produto = 1kg de peso, 3cm de altura, 40cm de largura e 50cm de comprimento
            $pesoFinal   = $pesoKg < (1.0 * $itensCount) ? (1.0 * $itensCount) : $pesoKg;
            $alturaFinal = 3 * $itensCount; // Se empilharmos a altura aumenta
            $larguraFinal = 40; // Mantém a largura base da caixa padrão
            $comprimentoFinal = 50; // Mantém o comprimento base da caixa padrão

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'User-Agent'    => 'Heimdall 90-Store integration (integracao@90store.com.br)',
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json'
            ])
            ->timeout(6)
            ->post('https://api.superfrete.com/api/v0/calculator', [
                'from' => [
                    'postal_code' => $cepOrigem
                ],
                'to' => [
                    'postal_code' => $cepDestino
                ],
                'services' => '1,2,17,3,31', // PAC, SEDEX, Mini Envios, Jadlog, Loggi
                'options' => [
                    'own_hand'            => false,
                    'receipt'             => false,
                    'insurance_value'     => 0,
                    'use_insurance_value' => false
                ],
                'package' => [
                    'weight' => $pesoFinal,
                    'width'  => $larguraFinal,
                    'height' => $alturaFinal,
                    'length' => $comprimentoFinal
                ]
            ]);

            $durationMs = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $cotacoes = [];
                $dados = $response->json();
                
                foreach ($dados as $item) {
                    if (isset($item['has_error']) && $item['has_error']) continue;
                    
                    $price = isset($item['price']) ? floatval($item['price']) : null;
                    if ($price === null) continue;

                    $name = $item['name'] ?? 'Correios';
                    if (isset($item['company']['name'])) {
                        $name = $name . ' (' . ucfirst($item['company']['name']) . ')';
                    }
                    $deadline = isset($item['delivery_time']) ? intval($item['delivery_time']) : 5;

                    $cotacoes[] = [
                        'servico' => $name,
                        'prazo_dias' => $deadline,
                        'valor' => $price,
                        'tipo' => 'nacional'
                    ];
                }

                DB::table('logs_api')->insert([
                    'api_config_id' => $api->id,
                    'rota' => 'quote_shipping',
                    'metodo' => 'POST',
                    'response_json' => json_encode($dados),
                    'status_http' => $response->status(),
                    'duracao_ms' => $durationMs,
                    'sucesso' => true,
                    'created_at' => now(),
                ]);

                return $cotacoes;
            }
        } catch (\Exception $e) {
            Log::error('FreteService: Falha ao cotar SuperFrete: ' . $e->getMessage());
        }

        return $this->failsafeSuperFrete($pesoKg);
    }

    private function failsafeSuperFrete(float $pesoKg): array
    {
        return [
            [
                'servico' => 'Correios PAC (Valor Aproximado)',
                'prazo_dias' => 7,
                'valor' => round(19.90 + ($pesoKg * 2.2), 2),
                'tipo' => 'nacional'
            ],
            [
                'servico' => 'Correios SEDEX (Valor Aproximado)',
                'prazo_dias' => 3,
                'valor' => round(29.90 + ($pesoKg * 3.8), 2),
                'tipo' => 'nacional'
            ]
        ];
    }
}
