<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attachments:cleanup')->everyTwoHours();
Schedule::command('posts:cleanup-generated-images')->hourly();
Schedule::command('trending:warm')
    ->everyFiveMinutes()
    ->name('warm-trending-cache')
    ->withoutOverlapping()
    ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('[Cron] warm-trending-cache failed'));
// Warm feed every 30 min (no AI)
Schedule::command('trending:warm')
    ->everyThirtyMinutes()
    ->name('warm-trending-cache')
    ->withoutOverlapping()
    ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('[Cron] warm-trending-cache failed'));

// Pre-warm AI for top 5 every 6 hours (matches AI cache TTL)
Schedule::command('trending:warm --with-ai')
    ->everySixHours()
    ->name('warm-trending-ai')
    ->withoutOverlapping()
    ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('[Cron] warm-trending-ai failed'));

