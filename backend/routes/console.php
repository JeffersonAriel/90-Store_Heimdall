<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sincronização automática de rastreamentos da SuperFrete e liberação de estoque expirado
Schedule::command('tracking:sync-superfrete')->hourly();
Schedule::command('stock:release-expired')->everyTenMinutes();
