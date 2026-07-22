<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;
use App\Models\ApiConfiguracao;
$api = ApiConfiguracao::where('slug', 'superfrete')->first();
$cred = is_array($api->credenciais_json) ? $api->credenciais_json : json_decode($api->credenciais_json, true);
$token = $cred['token'];

// Tenta gerar a tag usando o Order ID real (ULID)
$res1 = Http::withoutVerifying()
    ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json', 'Content-Type' => 'application/json'])
    ->post('https://api.superfrete.com/api/v0/tag/print', [
        'orders' => ['01KY5PHXBB8VYCRHBX9QXB9WNR']
    ]);
echo "Print via ULID: " . $res1->body() . "\n";

// Tenta gerar a tag usando o Codigo de Rastreio (1319...)
$res2 = Http::withoutVerifying()
    ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json', 'Content-Type' => 'application/json'])
    ->post('https://api.superfrete.com/api/v0/tag/print', [
        'orders' => ['13191900522997']
    ]);
echo "Print via Rastreio: " . $res2->body() . "\n";
