<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\ApiConfiguracao;

$api = ApiConfiguracao::where('slug', 'superfrete')->first();
$cred = is_array($api->credenciais_json) ? $api->credenciais_json : json_decode($api->credenciais_json, true);
$token = $cred['token'];

// 1. O ULID (26 chars) que o usuário disse que funciona direto
$ulid = '01KY5PHXBB8VYCRHBX9QXB9WNR';
$ulidB64 = base64_encode(json_encode(['order_id' => $ulid]));
$urlUlid = "https://etiqueta.superfrete.com/_etiqueta/pdf/{$ulidB64}?format=A6";

// 2. O NanoID (20 chars) que deu erro 500 no print
$nanoid = '1O36iiNLDRZMMvMpp3Mg'; // do screenshot atual
$nanoidB64 = base64_encode(json_encode(['order_id' => $nanoid]));
$urlNanoid = "https://etiqueta.superfrete.com/_etiqueta/pdf/{$nanoidB64}?format=A6";

echo "Testando URLs Públicas (SEM TOKEN)...\n\n";

$res1 = Http::withoutVerifying()->get($urlUlid);
echo "ULID Público ({$urlUlid}): " . $res1->status() . " (Tamanho: " . strlen($res1->body()) . ")\n";

$res2 = Http::withoutVerifying()->get($urlNanoid);
echo "NanoID Público ({$urlNanoid}): " . $res2->status() . " (Tamanho: " . strlen($res2->body()) . ")\n";

echo "\nTestando URLs com Autenticação (COM TOKEN)...\n\n";

$res3 = Http::withoutVerifying()->withHeaders(['Authorization' => "Bearer {$token}"])->get($urlUlid);
echo "ULID com Auth: " . $res3->status() . " (Tamanho: " . strlen($res3->body()) . ")\n";

$res4 = Http::withoutVerifying()->withHeaders(['Authorization' => "Bearer {$token}"])->get($urlNanoid);
echo "NanoID com Auth: " . $res4->status() . " (Tamanho: " . strlen($res4->body()) . ")\n";
