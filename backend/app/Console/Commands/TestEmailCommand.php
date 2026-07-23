<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Pedido;
use App\Mail\OrderStatusUpdatedMail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mail:test {email : O e-mail de destino para teste}';

    /**
     * The console command description.
     */
    protected $description = 'Dispara um e-mail de teste via HostGator Titan Mail para um e-mail específico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetEmail = $this->argument('email');

        $this->info("Tentando enviar e-mail de teste para: {$targetEmail}");
        $this->info("Servidor SMTP: " . config('mail.mailers.smtp.host') . ":" . config('mail.mailers.smtp.port'));
        $this->info("Remetente: " . config('mail.from.address'));

        try {
            // Tenta carregar um pedido de exemplo ou monta um modelo mock
            $pedido = Pedido::with(['cliente', 'endereco', 'itens.produto'])->first();

            if ($pedido) {
                Mail::to($targetEmail)->send(new OrderStatusUpdatedMail($pedido));
                $this->info("Sucesso! E-mail de teste com template do Pedido #{$pedido->id} enviado para {$targetEmail}.");
            } else {
                Mail::raw("E-mail de teste do Heimdall 90 Store via HostGator Titan Mail SSL (Porta 465).", function ($message) use ($targetEmail) {
                    $message->to($targetEmail)
                            ->subject("Teste de Conexão Titan Mail - 90 Store");
                });
                $this->info("Sucesso! E-mail simples de teste enviado para {$targetEmail}.");
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("Falha ao enviar e-mail:");
            $this->error($e->getMessage());
            return 1;
        }
    }
}
