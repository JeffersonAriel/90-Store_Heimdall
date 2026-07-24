<?php

require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use App\Services\DirectMailService;

$creds = DirectMailService::getCredentials();

echo "USER: " . $creds['username'] . "\n";
echo "PASS: " . $creds['password'] . "\n";

// Test 1: Using DSN with rawurlencode
$dsn1 = sprintf('smtps://%s:%s@%s:%d', rawurlencode($creds['username']), rawurlencode($creds['password']), $creds['host'], $creds['port']);
echo "DSN1: " . $dsn1 . "\n";

try {
    $transport1 = Transport::fromDsn($dsn1);
    $mailer1 = new Mailer($transport1);
    $email1 = (new Email())
        ->from(new Address($creds['from_address'], $creds['from_name']))
        ->to(new Address('tekoqz@gmail.com', 'Teste Admin'))
        ->subject('Teste DSN 1')
        ->text('Teste DSN 1');
    $mailer1->send($email1);
    echo "TEST 1 DSN SUCCESSFUL!\n";
} catch (\Throwable $e) {
    echo "TEST 1 DSN ERROR: " . $e->getMessage() . "\n";
}

// Test 2: Using EsmtpTransport directly with setUsername and setPassword
try {
    $transport2 = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
        $creds['host'],
        $creds['port'],
        true // TLS/SSL
    );
    $transport2->setUsername($creds['username']);
    $transport2->setPassword($creds['password']);

    $mailer2 = new Mailer($transport2);
    $email2 = (new Email())
        ->from(new Address($creds['from_address'], $creds['from_name']))
        ->to(new Address('tekoqz@gmail.com', 'Teste Admin'))
        ->subject('Teste EsmtpTransport Direct')
        ->text('Teste EsmtpTransport Direct');
    $mailer2->send($email2);
    echo "TEST 2 ESMTP SUCCESSFUL!\n";
} catch (\Throwable $e) {
    echo "TEST 2 ESMTP ERROR: " . $e->getMessage() . "\n";
}
