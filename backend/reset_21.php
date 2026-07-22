<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pedido = App\Models\Pedido::find(21);
if ($pedido) {
    $pedido->codigo_rastreio = null;
    $pedido->url_rastreio = null;
    $pedido->save();
    echo "Pedido 21 resetado com sucesso!\n";
} else {
    echo "Pedido 21 não encontrado.\n";
}
