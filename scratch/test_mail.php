<?php

require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MailConfigService;
use Illuminate\Support\Facades\Mail;

MailConfigService::apply();

echo "MAIL HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "MAIL PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "MAIL USER: " . config('mail.mailers.smtp.username') . "\n";
echo "MAIL PASS LEN: " . strlen(config('mail.mailers.smtp.password') ?? '') . "\n";
echo "MAIL ENCRYPTION: " . config('mail.mailers.smtp.encryption') . "\n";
echo "MAIL FROM: " . config('mail.from.address') . "\n";

try {
    Mail::raw('Teste de envio Titan Mail', function ($m) {
        $m->to('tekoqz@gmail.com')->subject('Teste Titan 90 Store');
    });
    echo "\n>>> E-MAIL ENVIADO COM SUCESSO! <<<\n";
} catch (\Throwable $e) {
    echo "\n>>> ERRO AO ENVIAR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
