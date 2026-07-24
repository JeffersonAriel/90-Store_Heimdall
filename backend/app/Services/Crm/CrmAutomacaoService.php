<?php

namespace App\Services\Crm;

use App\Models\Cliente;
use App\Models\Crm\CrmAutomacao;
use App\Models\Crm\CrmAutomacaoLog;
use App\Models\Crm\CrmAlerta;
use App\Models\Crm\CrmTarefa;
use App\Services\MailConfigService;
use App\Mail\CrmEmailMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * CrmAutomacaoService
 *
 * Processa automações por gatilho e manualmente com registro detalhado de logs.
 */
class CrmAutomacaoService
{
    /**
     * Processa todas as automações ativas por um determinado gatilho.
     */
    public static function processarGatilho(string $gatilho): int
    {
        $automacoes = CrmAutomacao::where('ativa', true)
            ->where('gatilho', $gatilho)
            ->get();

        $total = 0;

        foreach ($automacoes as $automacao) {
            $total += self::executarParaAutomacao($automacao);
        }

        return $total;
    }

    /**
     * Executa uma automação específica para todos os clientes elegíveis.
     */
    public static function executarParaAutomacao(CrmAutomacao $automacao): int
    {
        $clientes = self::getClientesElegiveis($automacao);
        $total = 0;

        foreach ($clientes as $cliente) {
            try {
                self::executarAcoes($automacao, $cliente);
                $total++;
            } catch (\Throwable $e) {
                Log::error("CRM Automação #{$automacao->id} erro cliente #{$cliente->id}: " . $e->getMessage());

                CrmAutomacaoLog::create([
                    'automacao_id'   => $automacao->id,
                    'cliente_id'     => $cliente->id,
                    'acao_executada' => 'erro',
                    'status'         => 'erro',
                    'erro_msg'       => $e->getMessage(),
                    'detalhes'       => [
                        'cliente_nome'  => $cliente->nome_social ?: $cliente->nome_completo,
                        'cliente_email' => $cliente->email ?? null,
                    ],
                    'executado_em'   => now(),
                ]);
            }
        }

        return $total;
    }

    /**
     * Retorna clientes elegíveis para uma automação específica.
     */
    private static function getClientesElegiveis(CrmAutomacao $automacao): \Illuminate\Support\Collection
    {
        $query = DB::table('clientes')
            ->where('ativo', true)
            ->whereNull('deleted_at');

        switch ($automacao->gatilho) {
            case 'apos_entrega':
                $dias = $automacao->delay_dias ?? 5;
                $dataAlvo = now()->subDays($dias)->format('Y-m-d');
                $query->where('ultimo_pedido_em', 'like', "{$dataAlvo}%");
                $query->whereExists(function ($q) use ($dataAlvo) {
                    $q->from('pedidos')
                        ->whereColumn('pedidos.cliente_id', 'clientes.id')
                        ->where('pedidos.status', 'entregue')
                        ->whereDate('pedidos.updated_at', $dataAlvo);
                });
                break;

            case 'dias_sem_compra':
                $dias = $automacao->delay_dias ?? 60;
                $query->where('ultimo_pedido_em', '<', now()->subDays($dias))
                    ->whereNotNull('ultimo_pedido_em');
                break;

            case 'aniversario':
                $query->whereRaw("DATE_FORMAT(data_nascimento, '%m-%d') = ?", [now()->format('m-d')])
                    ->whereNotNull('data_nascimento');
                break;

            case 'cliente_inativo':
                $dias = $automacao->delay_dias ?? 30;
                $query->where('ultimo_pedido_em', '<', now()->subDays($dias))
                    ->whereNotNull('ultimo_pedido_em');
                break;

            case 'primeira_compra':
                $query->where('total_pedidos_count', 1)
                    ->whereDate('primeiro_pedido_em', now()->subDays($automacao->delay_dias ?? 1)->toDateString());
                break;

            default:
                $query->whereNotNull('email');
                break;
        }

        // Evita executar a mesma automação duas vezes no mesmo dia para o mesmo cliente
        $jaExecutados = DB::table('crm_automacao_logs')
            ->where('automacao_id', $automacao->id)
            ->whereDate('created_at', today())
            ->pluck('cliente_id')
            ->toArray();

        if (!empty($jaExecutados)) {
            $query->whereNotIn('id', $jaExecutados);
        }

        return $query->select('id', 'nome_completo', 'nome_social', 'email', 'whatsapp', 'vendedor_id')
            ->limit(500)
            ->get();
    }

    /**
     * Executa as ações de uma automação para um cliente.
     */
    private static function executarAcoes(CrmAutomacao $automacao, object $cliente): void
    {
        $acoes = $automacao->acoes ?? [];

        // Aplica credenciais do Titan Mail
        MailConfigService::apply();

        foreach ($acoes as $acao) {
            $tipo  = $acao['tipo'] ?? 'enviar_email';
            $dados = $acao['dados'] ?? [];

            $nomeCliente = $cliente->nome_social ?: $cliente->nome_completo;

            // Busca último pedido do cliente para detalhamento no e-mail
            $ultimoPedido = \App\Models\Pedido::where('cliente_id', $cliente->id)
                ->with(['endereco', 'itens.produto'])
                ->orderByDesc('id')
                ->first();

            $numPedido    = $ultimoPedido ? "#{$ultimoPedido->id}" : '';
            $valorPedido  = $ultimoPedido ? 'R$ ' . number_format($ultimoPedido->total, 2, ',', '.') : '';
            $statusPedido = $ultimoPedido ? ucfirst($ultimoPedido->status) : '';
            $dataPedido   = $ultimoPedido ? $ultimoPedido->created_at->format('d/m/Y') : '';

            $rawAssunto  = $dados['assunto'] ?? $dados['titulo'] ?? 'Mensagem 90 Store';
            $rawMensagem = $dados['mensagem'] ?? $dados['descricao'] ?? 'Olá {{cliente}}, temos novidades!';

            $assunto = str_replace(
                ['{{cliente}}', '{{pedido}}', '{{valor}}', '{{status}}', '{{data}}'],
                [$nomeCliente, $numPedido, $valorPedido, $statusPedido, $dataPedido],
                $rawAssunto
            );

            $mensagem = str_replace(
                ['{{cliente}}', '{{pedido}}', '{{valor}}', '{{status}}', '{{data}}'],
                [$nomeCliente, $numPedido, $valorPedido, $statusPedido, $dataPedido],
                $rawMensagem
            );

            $statusGatilhos = [
                'status_pendente', 'pagamento_aprovado', 'status_em_separacao',
                'status_enviado', 'apos_entrega', 'status_cancelado', 'status_reembolsado'
            ];

            $precisaPedido = in_array($automacao->gatilho, $statusGatilhos)
                || str_contains($rawAssunto, '{{pedido}}')
                || str_contains($rawMensagem, '{{pedido}}')
                || str_contains($rawAssunto, '{{valor}}')
                || str_contains($rawMensagem, '{{valor}}')
                || str_contains($rawAssunto, '{{status}}')
                || str_contains($rawMensagem, '{{status}}');

            $pedidoExibir = $precisaPedido ? $ultimoPedido : null;

            switch ($tipo) {
                case 'enviar_email':
                    if (!empty($cliente->email)) {
                        $html = \App\Services\DirectMailService::renderBlade('emails.crm_email_html', [
                            'clienteNome'   => $nomeCliente,
                            'assuntoTexto'  => $assunto,
                            'mensagemTexto' => $mensagem,
                            'pedido'        => $pedidoExibir,
                        ]);

                        \App\Services\DirectMailService::sendDirect(
                            $cliente->email,
                            $nomeCliente,
                            $assunto,
                            $html
                        );
                    }
                    break;

                case 'criar_tarefa':
                    CrmTarefa::create([
                        'cliente_id'   => $cliente->id,
                        'responsavel_id'=> $cliente->vendedor_id,
                        'titulo'       => $assunto,
                        'descricao'    => $mensagem,
                        'tipo'         => $dados['tipo'] ?? 'contato',
                        'prioridade'   => $dados['prioridade'] ?? 'media',
                        'status'       => 'pendente',
                        'vencimento_em'=> now()->addDays(2),
                    ]);
                    break;

                case 'criar_alerta':
                    CrmAlerta::create([
                        'cliente_id'    => $cliente->id,
                        'responsavel_id'=> $cliente->vendedor_id,
                        'tipo'          => $dados['tipo'] ?? 'custom',
                        'titulo'        => $assunto,
                        'descricao'     => $mensagem,
                        'prioridade'    => $dados['prioridade'] ?? 'media',
                    ]);
                    break;
            }

            // Registra log completo da mensagem enviada
            CrmAutomacaoLog::create([
                'automacao_id'   => $automacao->id,
                'cliente_id'     => $cliente->id,
                'acao_executada' => $tipo,
                'status'         => 'sucesso',
                'detalhes'       => [
                    'cliente_nome'  => $nomeCliente,
                    'cliente_email' => $cliente->email ?? null,
                    'assunto'       => $assunto,
                    'mensagem'      => $mensagem,
                    'dados'         => $dados,
                ],
                'executado_em'   => now(),
            ]);
        }

        // Atualiza contadores
        $automacao->increment('total_execucoes');
        $automacao->increment('total_sucesso');

        // Registra na timeline do cliente
        CrmTimelineService::registrar('automacao_executada', $cliente->id,
            "Automação executada: {$automacao->nome}",
            ['origem' => 'automacao', 'metadata' => ['automacao_id' => $automacao->id]]
        );
    }
}
