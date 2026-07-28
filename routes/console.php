<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:poll', function () {
    $this->call(\App\Console\Commands\BotPolling::class);
})->purpose('Iniciar bot en modo polling para desarrollo local');

Artisan::command('telegram:set-webhook', function () {
    $this->call(\App\Console\Commands\SetTelegramWebhook::class);
})->purpose('Configurar el webhook del bot de Telegram');