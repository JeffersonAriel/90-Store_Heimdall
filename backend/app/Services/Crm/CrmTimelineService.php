<?php

namespace App\Services\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\Crm\CrmTimelineEvento;
use Illuminate\Support\Facades\Auth;

/**
 * CrmTimelineService
 *
 * Serviço central para registrar eventos na timeline CRM.
 * Chamado por controllers de Pedido, Financeiro, Checkout, etc.
 */
class CrmTimelineService
{
    /**
     * Registra um evento na timeline do cliente.
     */
    public static function registrar(
        string $tipo,
        ?int   $clienteId,
        string $titulo,
        array  $opcoes = []
    ): CrmTimelineEvento {
        $config  = CrmTimelineEvento::$tipoConfig[$tipo] ?? [];
        $usuario = self::resolverUsuario();

        return CrmTimelineEvento::create([
            'cliente_id'   => $clienteId,
            'lead_id'      => $opcoes['lead_id']     ?? null,
            'usuario_id'   => $usuario?->id,
            'usuario_nome' => $usuario?->nome ?? ($opcoes['usuario_nome'] ?? 'Sistema'),
            'tipo'         => $tipo,
            'titulo'       => $titulo,
            'descricao'    => $opcoes['descricao']   ?? null,
            'origem'       => $opcoes['origem']       ?? 'sistema',
            'icone'        => $opcoes['icone']        ?? ($config['icone'] ?? 'circle'),
            'cor'          => $opcoes['cor']          ?? ($config['cor']   ?? '#94a3b8'),
            'metadata'     => $opcoes['metadata']     ?? null,
        ]);
    }

    /**
     * Registra um evento na timeline de um lead.
     */
    public static function registrarLead(
        string $tipo,
        int    $leadId,
        string $titulo,
        array  $opcoes = []
    ): CrmTimelineEvento {
        return self::registrar($tipo, null, $titulo, array_merge($opcoes, [
            'lead_id' => $leadId,
        ]));
    }

    // ── Métodos de conveniência ───────────────────────────────────────────

    public static function pedidoCriado(int $clienteId, int $pedidoId, float $total): void
    {
        self::registrar('pedido_criado', $clienteId,
            "Pedido #{$pedidoId} criado — R$ " . number_format($total, 2, ',', '.'),
            ['origem' => 'ecommerce', 'metadata' => ['pedido_id' => $pedidoId, 'total' => $total]]
        );
    }

    public static function pedidoCancelado(int $clienteId, int $pedidoId, ?string $motivo = null): void
    {
        self::registrar('pedido_cancelado', $clienteId,
            "Pedido #{$pedidoId} cancelado" . ($motivo ? ": {$motivo}" : ''),
            ['origem' => 'sistema', 'metadata' => ['pedido_id' => $pedidoId, 'motivo' => $motivo]]
        );
    }

    public static function pagamentoAprovado(int $clienteId, int $pedidoId, string $metodo): void
    {
        self::registrar('pagamento_aprovado', $clienteId,
            "Pagamento aprovado — {$metodo} (Pedido #{$pedidoId})",
            ['origem' => 'sistema', 'metadata' => ['pedido_id' => $pedidoId, 'metodo' => $metodo]]
        );
    }

    public static function pagamentoRecusado(int $clienteId, int $pedidoId, string $metodo): void
    {
        self::registrar('pagamento_recusado', $clienteId,
            "Pagamento recusado — {$metodo} (Pedido #{$pedidoId})",
            ['origem' => 'sistema', 'metadata' => ['pedido_id' => $pedidoId, 'metodo' => $metodo]]
        );
    }

    public static function produtoEntregue(int $clienteId, int $pedidoId): void
    {
        self::registrar('produto_entregue', $clienteId,
            "Pedido #{$pedidoId} marcado como entregue",
            ['origem' => 'sistema', 'metadata' => ['pedido_id' => $pedidoId]]
        );
    }

    public static function clienteCriado(int $clienteId, string $nome, string $origem = 'ecommerce'): void
    {
        self::registrar('cliente_criado', $clienteId,
            "Cliente {$nome} cadastrado no sistema",
            ['origem' => $origem]
        );
    }

    public static function alteracaoCadastral(int $clienteId, string $campo): void
    {
        self::registrar('alteracao_cadastral', $clienteId,
            "Dados cadastrais atualizados: {$campo}",
            ['origem' => 'manual']
        );
    }

    public static function mudancaVendedor(int $clienteId, string $nomeVendedor): void
    {
        self::registrar('mudanca_vendedor', $clienteId,
            "Vendedor responsável alterado para: {$nomeVendedor}",
            ['origem' => 'manual']
        );
    }

    public static function anotacaoCriada(int $clienteId, string $titulo): void
    {
        self::registrar('anotacao_criada', $clienteId,
            "Nota criada: {$titulo}",
            ['origem' => 'manual']
        );
    }

    public static function tarefaCriada(int $clienteId, string $titulo): void
    {
        self::registrar('tarefa_criada', $clienteId,
            "Tarefa criada: {$titulo}",
            ['origem' => 'manual']
        );
    }

    public static function campanhaEnviada(int $clienteId, string $campanhaNome): void
    {
        self::registrar('campanha_enviada', $clienteId,
            "Campanha enviada: {$campanhaNome}",
            ['origem' => 'automacao']
        );
    }

    public static function pixPago(int $clienteId, int $pedidoId, float $valor): void
    {
        self::registrar('pix_pago', $clienteId,
            "PIX pago — R$ " . number_format($valor, 2, ',', '.') . " (Pedido #{$pedidoId})",
            ['origem' => 'sistema', 'metadata' => ['pedido_id' => $pedidoId, 'valor' => $valor]]
        );
    }

    public static function cupomUtilizado(int $clienteId, string $codigo, float $desconto): void
    {
        self::registrar('cupom_utilizado', $clienteId,
            "Cupom {$codigo} utilizado — desconto R$ " . number_format($desconto, 2, ',', '.'),
            ['origem' => 'ecommerce', 'metadata' => ['cupom' => $codigo, 'desconto' => $desconto]]
        );
    }

    // ── Resolução do usuário autenticado ──────────────────────────────────

    private static function resolverUsuario(): ?Funcionario
    {
        return Auth::guard('admin')->user();
    }
}
