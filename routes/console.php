<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Copia de seguridad diaria de la base de datos (Fase 11 / ADR-0014).
Schedule::command('dsle:backup')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();
