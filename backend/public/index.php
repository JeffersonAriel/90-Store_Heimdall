<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
