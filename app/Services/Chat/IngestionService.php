<?php

namespace App\Services\Chat;

use App\Models\Attachment;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory as WordIO;
use Smalot\PdfParser\Config as PdfConfig;
use Smalot\PdfParser\Parser as PdfParser;

class IngestionService
{
    private const MAX_PDF_PAGES = 10;
    private const MAX_CHARS     = 5000;

    /**
     * Download the attachment from Azure Blob Storage, extract its text
     * content, and persist it.
     *
     * Called at upload time from AttachmentController so that chat requests
     * are never delayed by heavy I/O.
     *
     * @return string|null  The extracted text, or null if extraction failed.
     */
    public function extractAndStore(Attachment $attachment): ?string
    {
        if ($attachment->status === 'processed') {
            return $attachment->text;
        }

        if ($attachment->type === 'image') {
            return null;
        }

        // blob_path replaces the old s3_path column
        $storagePath = $attachment->blob_path ?? $attachment->s3_path ?? null;

        if (!$storagePath) {
            throw new \RuntimeException("Attachment #{$attachment->id} is missing blob_path.");
        }

        $tempPath = null;

        try {
            $tempPath = tempnam(sys_get_temp_dir(), 'att_');

            // ── Read from Azure Blob Storage ──────────────────────────────
            $stream = Storage::disk('azure')->readStream($storagePath);

            if (!$stream) {
                throw new \RuntimeException("Failed to open Azure stream for: {$storagePath}");
            }

            $dest = fopen($tempPath, 'wb');
            stream_copy_to_stream($stream, $dest);
            fclose($dest);

            if (is_resource($stream)) {
                fclose($stream);
            }

            $text   = $this->extractFromPath($tempPath, $attachment->extension);
            $stored = $text ? substr($text, 0, self::MAX_CHARS) : null;

            $attachment->update([
                'text'   => $stored,
                'status' => 'processed',
            ]);

            return $stored;

        } catch (Exception $e) {
            Log::error('IngestionService: extraction failed', [
                'attachment_id' => $attachment->id,
                'error'         => $e->getMessage(),
            ]);

            $attachment->update(['status' => 'failed']);

            return null;

        } finally {
            if ($tempPath && file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Extract plain text from a local file path.
     * Dispatches to the appropriate parser based on file extension.
     */
    private function extractFromPath(string $path, string $ext): string
    {
        $ext = strtolower($ext);

        try {
            return match($ext) {
                'pdf'           => $this->extractPdf($path),
                'doc', 'docx'   => $this->extractWord($path),
                'txt'           => substr(file_get_contents($path), 0, self::MAX_CHARS),
                default         => '',
            };
        } catch (Exception $e) {
            Log::error('IngestionService: parser error', [
                'extension' => $ext,
                'error'     => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Extract text from a PDF file.
     * Font metadata parsing is disabled to reduce memory usage.
     * Only the first MAX_PDF_PAGES pages are read.
     */
    private function extractPdf(string $path): string
    {
        $config = new PdfConfig();
        $config->setFontSpaceLimit(-60);
        $config->setDataTmFontInfoHasToBeIncluded(false);

        $parser    = new PdfParser([], $config);
        $pages     = $parser->parseFile($path)->getPages();
        $text      = '';
        $pageCount = 0;

        foreach ($pages as $page) {
            if ($pageCount >= self::MAX_PDF_PAGES) {
                break;
            }

            $text .= $page->getText() . "\n";
            $pageCount++;
        }

        return $text;
    }

    /**
     * Extract plain text from a Word document (.doc / .docx).
     */
    private function extractWord(string $path): string
    {
        $phpWord = WordIO::load($path);
        $text    = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }

        return $text;
    }

    /**
     * @deprecated  Kept for backward compatibility with ProcessAttachmentJob.
     *              Use extractAndStore() for new code.
     */
    public function extractText($file): string
    {
        return $this->extractFromPath(
            $file->getPathname(),
            $file->getClientOriginalExtension()
        );
    }
}
