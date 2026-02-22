<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Services\Chat\IngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessAttachmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        public readonly int    $attachmentId,
        public readonly string $s3Path,
        public readonly string $extension
    ) {}

    public function handle(IngestionService $ingestion): void
    {
        $attachment = Attachment::find($this->attachmentId);
        if (!$attachment) return;

        try {
            // Download file from S3 to temp path
            $tempPath = tempnam(sys_get_temp_dir(), 'attachment_');
            file_put_contents($tempPath, Storage::disk('s3')->get($this->s3Path));

            // Create a fake file object for IngestionService
            $fakeFile = new \Illuminate\Http\File($tempPath);
            $fakeFile = new class($tempPath, $this->extension) {
                public function __construct(
                    private string $path,
                    private string $ext
                ) {}
                public function getPathname(): string { return $this->path; }
                public function getClientOriginalExtension(): string { return $this->ext; }
                public function getClientOriginalName(): string { return basename($this->path); }
            };

            $text = $ingestion->extractText($fakeFile);

            // Store only first 5000 chars to avoid huge DB payloads
            $attachment->update([
                'text'   => $text ? substr($text, 0, 5000) : null,
                'status' => 'processed',
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessAttachmentJob failed', [
                'attachment_id' => $this->attachmentId,
                'error'         => $e->getMessage(),
            ]);
            $attachment->update(['status' => 'failed']);
        } finally {
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
