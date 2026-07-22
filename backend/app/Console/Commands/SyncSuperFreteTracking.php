<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use App\Models\ApiConfiguracao;
use App\Services\OrderStatusService;
use App\Http\Controllers\Api\SuperFreteWebhookController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncSuperFreteTracking extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tracking:sync-superfrete';

    /**
     * The console command description.
     */
    protected $description = 'Sincroniza automaticamente o status de rastreamento dos pedidos na SuperFrete (Enviado, Entregue)';

    /**
     * Execute the console command.
     */
    public function handle(OrderStatusService $orderStatusService)
    {
        $this->info('Iniciando sincronização automática de rastreamento com a SuperFrete...');

        $api = ApiConfiguracao::where('slug', 'superfrete')->where('ativo', true)->first();
        if (!$api) {
            $this->warn('API da SuperFrete não está ativa.');
            return 0;
        }

        $creds = is_string($api->credenciais_json) ? json_decode($api->credenciais_json, true) : $api->credenciais_json;
        $token = $creds['token'] ?? null;

        if (!$token) {
            $this->error('Token da SuperFrete não configurado.');
            return 1;
        }

        // Busca pedidos em aberto com código de rastreamento que ainda não foram marcados como entregues
        $orders = Pedido::whereIn('status', ['em_separacao', 'em_envio', 'enviado'])
            ->whereNotNull('codigo_rastreio')
            ->where('codigo_rastreio', '!=', '')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Nenhum pedido pendente de atualização de rastreio.');
            return 0;
        }

        $updatedCount = 0;

        foreach ($orders as $order) {
            try {
                // Consulta rastreamento via API oficial da SuperFrete
                $response = Http::withoutVerifying()
                    ->withHeaders([
                        'Authorization' => "Bearer {$token}",
                        'Accept'        => 'application/json',
                        'User-Agent'    => 'Heimdall 90-Store'
                    ])
                    ->post('https://api.superfrete.com/api/v0/tracking', [
                        'codes' => [$order->codigo_rastreio]
                    ]);

                if ($response->successful()) {
                    $trackingData = $response->json();
                    
                    // Extrai eventos de rastreio retornados
                    $events = $trackingData[0]['events'] ?? $trackingData['events'] ?? $trackingData['status'] ?? [];
                    $lastStatusStr = '';

                    if (is_array($events) && !empty($events)) {
                        $lastEvent = end($events);
                        $lastStatusStr = is_array($lastEvent) ? ($lastEvent['status'] ?? $lastEvent['description'] ?? '') : (string)$lastEvent;
                    } elseif (is_string($events)) {
                        $lastStatusStr = $events;
                    }

                    if ($lastStatusStr) {
                        $targetStatus = SuperFreteWebhookController::mapTrackingStatus($lastStatusStr);
                        if ($targetStatus && $targetStatus !== $order->status) {
                            $orderStatusService->advanceToStatus(
                                $order->id,
                                $targetStatus,
                                null,
                                "Status sincronizado automaticamente com a SuperFrete: {$lastStatusStr}"
                            );
                            $this->info("Pedido #{$order->id} ({$order->codigo_rastreio}) atualizado para '{$targetStatus}'.");
                            $updatedCount++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Erro ao sincronizar rastreio do pedido #{$order->id}: " . $e->getMessage());
            }
        }

        $this->info("Sincronização concluída. {$updatedCount} pedido(s) atualizados com sucesso.");
        return 0;
    }
}
