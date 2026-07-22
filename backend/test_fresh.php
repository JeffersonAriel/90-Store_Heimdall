<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;
use App\Models\ApiConfiguracao;
$api = ApiConfiguracao::where('slug', 'superfrete')->first();
$cred = is_array($api->credenciais_json) ? $api->credenciais_json : json_decode($api->credenciais_json, true);
$token = $cred['token'];

$res = Http::withoutVerifying()
    ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json', 'Content-Type' => 'application/json'])
    ->post('https://api.superfrete.com/api/v0/tag/print', [
        'orders' => ['13191900522997']
    ]);

$url = $res->json('url');
echo "Nova URL gerada: {$url}\n";

// Tenta pegar a URL com Auth
$resAuth = Http::withoutVerifying()->withHeaders(['Authorization' => "Bearer {$token}"])->get($url);
echo "Status Com Auth: " . $resAuth->status() . " (Size: " . strlen($resAuth->body()) . ")\n";

// Tenta pegar a URL sem Auth
$resNoAuth = Http::withoutVerifying()->get($url);
echo "Status Sem Auth: " . $resNoAuth->status() . " (Size: " . strlen($resNoAuth->body()) . ")\n";
