<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attachments:cleanup')->everyTwoHours();
Schedule::command('posts:cleanup-generated-images')->hourly();
Schedule::command('notifications:cleanup')->daily();

// Warm trending posts + tech trends feed every 30 min (no AI — feed stays fast)
Schedule::command('trending:warm')
    ->everyThirtyMinutes()
    ->name('warm-trending-cache')
    ->withoutOverlapping()
    ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('[Cron] warm-trending-cache failed'));
