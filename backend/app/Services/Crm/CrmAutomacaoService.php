<?php

namespace App\Services\Crm;

use App\Models\Cliente;
use App\Models\Crm\CrmAutomacao;
use App\Models\Crm\CrmAutomacaoLog;
use App\Models\Crm\CrmAlerta;
use App\Models\Crm\CrmTarefa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CrmAutomacaoService
 *
 * Processa automações por gatilho.
 * Chamado via comando artisan ou schedule diário.
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
            $clientes = self::getClientesElegiveis($automacao);

            foreach ($clientes as $cliente) {
                try {
                    self::executarAcoes($automacao, $cliente);
                    $total++;
                } catch (\Exception $e) {
                    Log::error("CRM Automação #{$automacao->id} erro cliente #{$cliente->id}: " . $e->getMessage());
                }
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
                // Verifica se o último pedido foi entregue
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
                return collect();
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

        foreach ($acoes as $acao) {
            $tipo  = $acao['tipo'] ?? null;
            $dados = $acao['dados'] ?? [];

            $nomeCliente = $cliente->nome_social ?: $cliente->nome_completo;

            $titulo = str_replace('{{cliente}}', $nomeCliente, $dados['titulo'] ?? 'Tarefa automática');

            switch ($tipo) {
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
