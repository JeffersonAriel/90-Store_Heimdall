<?php

require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use App\Services\DirectMailService;

$creds = DirectMailService::getCredentials();

try {
    $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
        $creds['host'],
        $creds['port'],
        true
    );
    $transport->setLocalDomain('www.90store.com.br');
    $transport->setUsername($creds['username']);
    $transport->setPassword($creds['password']);

    $mailer = new Mailer($transport);
    $email = (new Email())
        ->from(new Address($creds['from_address'], $creds['from_name']))
        ->to(new Address('tekoqz@gmail.com', 'Teste Admin'))
        ->subject('Teste EHLO Domain Titan Mail')
        ->text('Teste EHLO Domain Titan Mail');
    $mailer->send($email);
    echo "TEST EHLO DOMAIN SUCCESSFUL!\n";
} catch (\Throwable $e) {
    echo "TEST EHLO DOMAIN ERROR: " . $e->getMessage() . "\n";
}
