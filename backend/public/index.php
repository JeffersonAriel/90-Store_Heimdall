<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─── AUTO-INICIALIZADOR PARA DEPLOY LIMPO ───
$vendorPath = __DIR__.'/../vendor';
$zipPath = __DIR__.'/../vendor.zip';

// 1. Extração automática do vendor.zip se a pasta vendor não existir
if (!is_dir($vendorPath) && file_exists($zipPath)) {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo(__DIR__.'/../');
            $zip->close();
        }
    }
}

// 2. Criação automática do .env a partir do .env.example
$envPath = __DIR__.'/../.env';
$envExamplePath = __DIR__.'/../.env.example';

if (!file_exists($envPath)) {
    if (file_exists($envExamplePath)) {
        copy($envExamplePath, $envPath);
    } else {
        touch($envPath);
    }
}

// 3. Geração automática de APP_KEY caso esteja vazia no .env
if (file_exists($envPath)) {
    $envContent = file_get_contents($envPath);
    $hasKey = false;
    
    if (preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches)) {
        if (!empty(trim($matches[1]))) {
            $hasKey = true;
        }
    }
    
    if (!$hasKey) {
        $generatedKey = 'base64:' . base64_encode(random_bytes(32));
        if (preg_match('/^APP_KEY=/m', $envContent)) {
            $envContent = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$generatedKey}", $envContent);
        } else {
            $envContent = "APP_KEY={$generatedKey}\n" . $envContent;
        }
        file_put_contents($envPath, $envContent);
    }
}

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Corrige o roteamento quando acessado via URL amigável em subpastas do cPanel
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = preg_replace('/\/index\.php$/', '', $scriptName);
$appBase = preg_replace('/\/backend\/public$/', '', $scriptDir);
if ($scriptDir && strpos($_SERVER['REQUEST_URI'] ?? '', $scriptDir) !== 0) {
    $_SERVER['SCRIPT_NAME'] = $appBase . '/index.php';
    $_SERVER['PHP_SELF'] = $appBase . '/index.php';
}

$request = Request::capture();

$app->handleRequest($request);
