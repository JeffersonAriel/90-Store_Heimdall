<?php

require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MailConfigService;
use Illuminate\Support\Facades\Mail;
use App\Mail\CrmEmailMail;
use App\Models\Pedido;

MailConfigService::apply();

$pedido = Pedido::with(['endereco', 'itens.produto'])->orderByDesc('id')->first();

try {
    Mail::to('tekoqz@gmail.com')->send(
        new CrmEmailMail(
            'Administrador (Teste)',
            'Teste HTML Profissional 90 Store',
            "Olá!\n\nEste é o teste do novo modelo HTML profissional com o símbolo da 90 Store ★ 90 Mais e o resumo do pedido.",
            $pedido
        )
    );
    echo "\n>>> CRM HTML EMAIL ENVIADO COM SUCESSO! <<<\n";
} catch (\Throwable $e) {
    echo "\n>>> ERRO AO ENVIAR CRM EMAIL: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
