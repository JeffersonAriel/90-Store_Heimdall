<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CepService
{
    /**
     * Busca dados de endereço pelo CEP usando a cadeia de fallback configurável.
     * Sequência: 1) ViaCEP -> 2) BrasilAPI -> 3) ApiCEP
     */
    public function buscar(string $cep): array
    {
        $cep = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($cep) !== 8) {
            return ['success' => false, 'message' => 'CEP inválido. Formato deve conter 8 dígitos.'];
        }

        // Obtém a ordem de fallback configurada no banco (apis_configuracao)
        $apis = DB::table('apis_configuracao')
            ->where('tipo', 'cep')
            ->where('ativo', true)
            ->orderBy('fallback_ordem', 'asc')
            ->get();

        if ($apis->isEmpty()) {
            // Fallback hardcoded de segurança se não houver registros no banco
            return $this->executarFallbackPadrao($cep);
        }

        foreach ($apis as $api) {
            $startTime = microtime(true);
            try {
                $resultado = null;

                if ($api->slug === 'viacep') {
                    $resultado = $this->chamarViaCep($cep);
                } elseif ($api->slug === 'brasilapi') {
                    $resultado = $this->chamarBrasilApi($cep);
                } elseif ($api->slug === 'apicep') {
                    $resultado = $this->chamarApiCep($cep);
                }

                $durationMs = round((microtime(true) - $startTime) * 1000);

                if ($resultado && $resultado['success']) {
                    // Loga sucesso da chamada de API no banco de logs
                    $this->registrarLogApi($api->id, "buscar/{$cep}", 'GET', null, $resultado, 200, $durationMs, true);
                    return array_merge($resultado, ['api_origem' => $api->nome]);
                }

                // Se falhou, registra o log de erro e tenta a próxima da cadeia
                $this->registrarLogApi($api->id, "buscar/{$cep}", 'GET', null, $resultado, 400, $durationMs, false, 'API retornou erro ou formato inválido.');

            } catch (\Exception $e) {
                $durationMs = round((microtime(true) - $startTime) * 1000);
                $this->registrarLogApi($api->id, "buscar/{$cep}", 'GET', null, null, 500, $durationMs, false, $e->getMessage());
                Log::warning("CepService: Falha na API {$api->nome} ao buscar CEP {$cep}. Tentando próxima da cadeia. Erro: " . $e->getMessage());
            }
        }

        return ['success' => false, 'message' => 'Não foi possível buscar as informações do CEP em nenhuma das APIs de fallback.'];
    }

    private function chamarViaCep(string $cep): ?array
    {
        $response = Http::timeout(3)->get("https://viacep.com.br/ws/{$cep}/json/");

        if ($response->successful() && !isset($response['erro'])) {
            return [
                'success'    => true,
                'cep'        => $response['cep'],
                'logradouro' => $response['logradouro'],
                'complemento'=> $response['complemento'],
                'bairro'     => $response['bairro'],
                'cidade'     => $response['localidade'],
                'estado'     => $response['uf'],
            ];
        }

        return null;
    }

    private function chamarBrasilApi(string $cep): ?array
    {
        $response = Http::timeout(3)->get("https://brasilapi.com.br/api/cep/v1/{$cep}");

        if ($response->successful()) {
            return [
                'success'    => true,
                'cep'        => $response['cep'],
                'logradouro' => $response['street'] ?? '',
                'complemento'=> '',
                'bairro'     => $response['neighborhood'] ?? '',
                'cidade'     => $response['city'],
                'estado'     => $response['state'],
            ];
        }

        return null;
    }

    private function chamarApiCep(string $cep): ?array
    {
        // Aceita cep com hífen ou busca estruturada na ApiCEP/OpenCEP
        $response = Http::timeout(3)->get("https://opencep.com/v1/{$cep}");

        if ($response->successful()) {
            return [
                'success'    => true,
                'cep'        => $response['cep'],
                'logradouro' => $response['logradouro'] ?? '',
                'complemento'=> $response['complemento'] ?? '',
                'bairro'     => $response['bairro'] ?? '',
                'cidade'     => $response['localidade'],
                'estado'     => $response['uf'],
            ];
        }

        return null;
    }

    private function executarFallbackPadrao(string $cep): array
    {
        Log::info("CepService: Executando fallback padrão hardcoded para CEP {$cep}.");
        $resultado = $this->chamarViaCep($cep);
        if ($resultado) return $resultado;

        $resultado = $this->chamarBrasilApi($cep);
        if ($resultado) return $resultado;

        $resultado = $this->chamarApiCep($cep);
        if ($resultado) return $resultado;

        return ['success' => false, 'message' => 'Todas as APIs padrão falharam.'];
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
            Log::error('CepService: Falha ao gravar log da API: ' . $e->getMessage());
        }
    }
}
