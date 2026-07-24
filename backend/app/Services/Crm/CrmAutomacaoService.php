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
 * Processa automações por gatilho e manualmente.
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
            } catch (\Exception $e) {
                Log::error("CRM Automação #{$automacao->id} erro cliente #{$cliente->id}: " . $e->getMessage());
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
                // Se gatilho for customizado ou sem filtro estrito, retorna clientes com pedidos
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

        // Garante aplicação do SMTP para envios de e-mail
        MailConfigService::apply();

        foreach ($acoes as $acao) {
            $tipo  = $acao['tipo'] ?? null;
            $dados = $acao['dados'] ?? [];

            $nomeCliente = $cliente->nome_social ?: $cliente->nome_completo;
            $titulo = str_replace('{{cliente}}', $nomeCliente, $dados['titulo'] ?? $dados['assunto'] ?? 'Automação CRM 90 Store');

            switch ($tipo) {
                case 'enviar_email':
                    if (!empty($cliente->email)) {
                        $assunto = str_replace('{{cliente}}', $nomeCliente, $dados['assunto'] ?? 'Mensagem Especial 90 Store');
                        $mensagem = str_replace('{{cliente}}', $nomeCliente, $dados['mensagem'] ?? $dados['descricao'] ?? 'Olá {{cliente}}, temos novidades para você!');

                        Mail::to($cliente->email)->send(new CrmEmailMail($nomeCliente, $assunto, $mensagem));
                    }
                    break;

                case 'criar_tarefa':
                    CrmTarefa::create([
                        'cliente_id'   => $cliente->id,
                        'responsavel_id'=> $cliente->vendedor_id,
                        'titulo'       => $titulo,
                        'descricao'    => $dados['descricao'] ?? null,
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
                        'titulo'        => $titulo,
                        'descricao'     => $dados['descricao'] ?? null,
                        'prioridade'    => $dados['prioridade'] ?? 'media',
                    ]);
                    break;
            }

            // Registra log de execução
            CrmAutomacaoLog::create([
                'automacao_id'   => $automacao->id,
                'cliente_id'     => $cliente->id,
                'acao_executada' => $tipo,
                'status'         => 'sucesso',
                'detalhes'       => ['dados' => $dados, 'cliente' => $nomeCliente],
                'executado_em'   => now(),
            ]);
        }

        // Atualiza contadores
        $automacao->increment('total_execucoes');
        $automacao->increment('total_sucesso');

        // Registra na timeline
        CrmTimelineService::registrar('automacao_executada', $cliente->id,
            "Automação executada: {$automacao->nome}",
            ['origem' => 'automacao', 'metadata' => ['automacao_id' => $automacao->id]]
        );
    }
}
