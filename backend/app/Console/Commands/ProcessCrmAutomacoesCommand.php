<?php

namespace App\Console\Commands;

use App\Services\Crm\CrmAutomacaoService;
use Illuminate\Console\Command;

class ProcessCrmAutomacoesCommand extends Command
{
    /**
     * O nome e assinatura do comando no terminal.
     */
    protected $signature = 'crm:process-automacoes {gatilho? : Gatilho específico a processar}';

    /**
     * A descrição do comando.
     */
    protected $description = 'Processa as automações ativas do CRM (e-mails, alertas e tarefas)';

    /**
     * Executa o comando.
     */
    public function handle(): int
    {
        $gatilho = $this->argument('gatilho');

        $gatilhos = $gatilho ? [$gatilho] : [
            'aniversario',
            'apos_entrega',
            'dias_sem_compra',
            'cliente_inativo',
            'primeira_compra',
            'carrinho_abandonado',
        ];

        $this->info("Iniciando processamento de automações do CRM...");
        $totalExecucoes = 0;

        foreach ($gatilhos as $g) {
            $count = CrmAutomacaoService::processarGatilho($g);
            $totalExecucoes += $count;
            if ($count > 0) {
                $this->info("Gatilho '{$g}': {$count} cliente(s) processado(s).");
            }
        }

        $this->info("Processamento concluído. Total de ações executadas: {$totalExecucoes}");

        return Command::SUCCESS;
    }
}
