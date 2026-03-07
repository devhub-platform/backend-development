<?php

namespace App\Console\Commands;

use App\Models\GeneratedPostImage;
use App\Services\ImageUploadCloudinaryService;
use Illuminate\Console\Command;

/**
 * Delete pending generated images that were never confirmed.
 * Register in routes/console.php:
 *   Schedule::command('posts:cleanup-generated-images')->daily();
 */
class CleanupGeneratedImages extends Command
{
    protected $signature   = 'posts:cleanup-generated-images
                              {--hours=24 : Delete pending images older than this many hours}
                              {--dry-run  : Preview without deleting}';

    protected $description = 'Remove unconfirmed AI-generated images from Cloudinary and DB';

    public function handle(ImageUploadCloudinaryService $cloudinary): int
    {
        $hours  = (int) $this->option('hours');
        $dryRun = (bool) $this->option('dry-run');

        $images = GeneratedPostImage::where('status', 'pending')
            ->where('created_at', '<', now()->subHours($hours))
            ->get();

        $this->info("Found {$images->count()} unconfirmed image(s) older than {$hours} hour(s).");

        if ($dryRun || $images->isEmpty()) {
            return self::SUCCESS;
        }

        $deleted = 0;

        foreach ($images as $image) {
            if ($image->secure_url) {
                $cloudinary->deleteImage($image->secure_url);
            }
            $image->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} unconfirmed image(s).");

        return self::SUCCESS;
    }
}
