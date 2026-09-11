<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Permanently purge expired chat attachments daily (a self-healing
// opportunistic purge also runs from the chat component if cron is absent).
Schedule::command('chat:purge-attachments')->daily();
