<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Register in routes/console.php:
 *   Schedule::command('attachments:cleanup')->daily();
 */
class CleanupAttachments extends Command
{
    protected $signature = 'attachments:cleanup
                            {--failed-days=7    : Delete failed attachments older than this many days}
                            {--orphan-hours=6   : Delete processed attachments not linked to any message after this many hours}
                            {--dry-run          : Preview without deleting}';

    protected $description = 'Remove failed and orphaned processed attachments from S3 and DB';

    public function handle(): int
    {
        $failedDays  = (int) $this->option('failed-days');
        $orphanHours = (int) $this->option('orphan-hours');
        $dryRun      = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('[dry-run] No data will be modified.');
        }

        $this->cleanupFailed($failedDays, $dryRun);
        $this->cleanupOrphanedProcessed($orphanHours, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Delete attachments that failed extraction and are older than $days days.
     */
    private function cleanupFailed(int $days, bool $dryRun): void
    {
        $attachments = Attachment::where('status', 'failed')
            ->where('created_at', '<', now()->subDays($days))
            ->get();

        $this->info("Failed: found {$attachments->count()} attachment(s) older than {$days} day(s).");

        if ($dryRun || $attachments->isEmpty()) {
            return;
        }

        $this->deleteAttachments($attachments);
    }

    /**
     * Delete processed attachments that are not referenced in any chat message.
     *
     * An attachment is "orphaned" when:
     *   - Its ID does not appear in any ai_chat_messages.attachments JSON column
     *   - It has been sitting unlinked for more than $hours hours
     *
     * This covers uploads where the user uploaded a file but never sent a message.
     */
    private function cleanupOrphanedProcessed(int $hours, bool $dryRun): void
    {
        // Pull all attachment IDs that are referenced in at least one message
        $referencedIds = DB::table('ai_chat_messages')
            ->whereNotNull('attachments')
            ->pluck('attachments')
            ->flatMap(fn($json) => json_decode($json, true) ?? [])
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $attachments = Attachment::where('status', 'processed')
            ->where('created_at', '<', now()->subHours($hours))
            ->when(!empty($referencedIds), fn($q) => $q->whereNotIn('id', $referencedIds))
            ->get();

        $this->info("Orphaned: found {$attachments->count()} processed attachment(s) not linked to any message after {$hours} hour(s).");

        if ($dryRun || $attachments->isEmpty()) {
            return;
        }

        $this->deleteAttachments($attachments);
    }

    private function deleteAttachments($attachments): void
    {
        $deleted = 0;

        foreach ($attachments as $attachment) {
            if ($attachment->s3_path) {
                try {
                    Storage::disk('s3')->delete($attachment->s3_path);
                } catch (\Exception) {
                    $this->warn("Could not delete S3 object: {$attachment->s3_path}");
                }
            }

            $attachment->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} attachment(s).");
    }
}
