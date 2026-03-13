<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule inventory alerts check to run hourly
Schedule::command('alerts:check')->hourly();

// Limpiar entradas expiradas de la tabla cache cada hora
Schedule::call(function () {
    DB::table('cache')->where('expiration', '<', now()->timestamp)->delete();
})->hourly()->description('Limpiar entradas de cache expiradas');
