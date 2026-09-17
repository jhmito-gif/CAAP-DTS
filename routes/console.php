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

// Read newly arrived documents so they can be searched by their contents.
// Scans go through OCR, so each run takes a batch and stops; the next run
// carries on. "Read now" in the explorer covers anything wanted sooner.
Schedule::command('documents:read --limit=20 --retry')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Clear away the pieces of uploads that were never finished.
Schedule::command('documents:purge-chunks')->dailyAt('01:30');
