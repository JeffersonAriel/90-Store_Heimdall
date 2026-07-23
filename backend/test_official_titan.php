<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'noreply@90store.com.br';
$password = 'Store90Mais1910!';

echo "Testing SMTP Titan Mail Official Configuration:\n";
echo "Host: smtp.titan.email\n";
echo "Port: 465 (SSL)\n";
echo "User: {$email}\n";
echo "Pass: {$password}\n\n";

config([
    'mail.mailers.smtp.host' => 'smtp.titan.email',
    'mail.mailers.smtp.port' => 465,
    'mail.mailers.smtp.scheme' => 'smtps',
    'mail.mailers.smtp.username' => $email,
    'mail.mailers.smtp.password' => $password,
    'mail.from.address' => $email,
    'mail.from.name' => '90 Store',
]);

Mail::purge('smtp');

try {
    Mail::raw("Olá! Este é um teste oficial de envio do Heimdall 90 Store via HostGator Titan Mail SSL (Porta 465).", function ($message) {
        $message->to("tekoqz@gmail.com")
                ->subject("🎉 Teste Oficial de E-mail Titan Mail - 90 Store");
    });
    echo "=========================================\n";
    echo "SUCCESS! E-mail ENTREGUE COM SUCESSO no servidor Titan Mail!\n";
    echo "Destinatário: tekoqz@gmail.com\n";
    echo "=========================================\n";
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}
