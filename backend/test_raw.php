<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Http;
use App\Models\ApiConfiguracao;
$api = ApiConfiguracao::where('slug', 'superfrete')->first();
$cred = is_array($api->credenciais_json) ? $api->credenciais_json : json_decode($api->credenciais_json, true);
$token = $cred['token'];

$url = 'https://etiqueta.superfrete.com/_etiqueta/pdf/1O36iiNLDRZMMvMpp3Mg?format=A6';

echo 'NanoID URL crua sem auth: ' . Http::withoutVerifying()->get($url)->status() . "\n";
echo 'NanoID URL crua com auth: ' . Http::withoutVerifying()->withHeaders(['Authorization' => 'Bearer ' . $token])->get($url)->status() . "\n";
