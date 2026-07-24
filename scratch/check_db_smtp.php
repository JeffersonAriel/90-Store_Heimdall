<?php

require __DIR__ . '/../backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApiConfiguracao;

$api = ApiConfiguracao::where('slug', 'smtp_mail')->first();

if ($api) {
    echo "API FOUND!\n";
    echo "TIPO: " . $api->tipo . "\n";
    echo "SLUG: " . $api->slug . "\n";
    echo "NOME: " . $api->nome . "\n";
    echo "ATIVO: " . ($api->ativo ? 'SIM' : 'NÃO') . "\n";
    echo "CREDENCIAIS JSON:\n";
    var_dump($api->credenciais_json);
} else {
    echo "API NOT FOUND IN DB!\n";
}

echo "MAIL_PASSWORD ENV LEN: " . strlen(env('MAIL_PASSWORD') ?? '') . "\n";
