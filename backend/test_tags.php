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
    ->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])
    ->get('https://api.superfrete.com/api/v0/tag');

echo "Tags:\n" . substr($res->body(), 0, 1000) . "\n";
