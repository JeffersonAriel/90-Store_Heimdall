<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Services\OrderStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuperFreteWebhookController extends Controller
{
    protected $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
    }

    /**
     * Recebe notificações via Webhook da SuperFrete e atualiza o status do pedido no Heimdall
     */
    public function handle(Request $request)
    {
        Log::info('SuperFrete Webhook recebido', $request->all());

        $data = $request->all();
        $code = $data['code'] ?? $data['tracking'] ?? $data['tracking_code'] ?? $data['tag'] ?? null;
        $orderId = $data['external_id'] ?? $data['order_id'] ?? null;
        $statusStr = strtolower($data['status'] ?? $data['event'] ?? $data['type'] ?? $data['message'] ?? '');

        // Localiza o pedido por código de rastreio ou ID
        $pedido = null;
        if ($code) {
            $pedido = Pedido::where('codigo_rastreio', $code)->first();
        }
        if (!$pedido && $orderId) {
            $cleanId = (int) str_replace('PED', '', $orderId);
            $pedido = Pedido::find($cleanId);
        }

        if (!$pedido) {
            Log::warning('SuperFrete Webhook: Pedido não encontrado.', $data);
            return response()->json(['message' => 'Pedido não encontrado.'], 404);
        }

        // Mapeia a string de rastreamento para o status interno
        $targetStatus = static::mapTrackingStatus($statusStr);

        if ($targetStatus) {
            try {
                $this->orderStatusService->advanceToStatus(
                    $pedido->id,
                    $targetStatus,
                    null,
                    "Atualização automática via Webhook SuperFrete (Evento: {$statusStr})."
                );
                return response()->json([
                    'success' => true,
                    'message' => "Pedido #{$pedido->id} atualizado com sucesso para '{$targetStatus}'."
                ], 200);
            } catch (\Exception $e) {
                Log::error("SuperFrete Webhook: Erro ao atualizar pedido #{$pedido->id}: " . $e->getMessage());
                return response()->json(['message' => $e->getMessage()], 400);
            }
        }

        return response()->json(['message' => 'Evento processado sem alteração de status.'], 200);
    }

    /**
     * Mapeia status de rastreamento da SuperFrete / Correios / Jadlog para os status do Heimdall
     */
    public static function mapTrackingStatus(string $statusStr): ?string
    {
        $statusStr = strtolower($statusStr);

        if (
            str_contains($statusStr, 'delivered') || 
            str_contains($statusStr, 'entregue') || 
            str_contains($statusStr, 'objeto entregue') ||
            str_contains($statusStr, 'saiu para entrega e foi entregue')
        ) {
            return 'entregue';
        }

        if (
            str_contains($statusStr, 'posted') || 
            str_contains($statusStr, 'postado') || 
            str_contains($statusStr, 'in_transit') || 
            str_contains($statusStr, 'em_transito') || 
            str_contains($statusStr, 'em transito') || 
            str_contains($statusStr, 'shipped') ||
            str_contains($statusStr, 'out_for_delivery') ||
            str_contains($statusStr, 'saiu para entrega')
        ) {
            return 'enviado';
        }

        return null;
    }
}
