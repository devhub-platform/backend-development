<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup
                            {--days=7 : Remove notifications older than this many days}
                            {--dry-run : Preview without deleting}';

    protected $description = 'Prune old notification records';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $query = DatabaseNotification::query()
            ->where('created_at', '<', now()->subDays($days));

        $count = (clone $query)->count();

        $this->info("Found {$count} notification(s) older than {$days} day(s).");

        if ($dryRun || $count === 0) {
            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Deleted {$deleted} notification(s).");

        return self::SUCCESS;
    }
}
