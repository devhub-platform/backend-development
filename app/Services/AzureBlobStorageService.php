<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Thin wrapper around the 'azure' Laravel Storage disk.
 *
 * Handles:
 *  - Standard UploadedFile objects (multipart/form-data)
 *  - Raw base64-encoded strings / data-URIs
 *  - Optional JPEG compression for images before upload
 *
 * All files land under {container}/{$folder}/{filename}
 * and are publicly readable (container must have anonymous blob read access).
 *
 * Return contract:
 *  - uploadFile()   → array{ url: string, path: string }
 *  - uploadBase64() → array{ url: string, path: string }
 *  - uploadImage()  → string  (legacy shim — returns URL only)
 */
class AzureBlobStorageService
{
    /** JPEG quality 1–100 used when compressing images before upload */
    private const JPEG_QUALITY = 75;

    /** Base folder inside the container for chat attachments */
    private const DEFAULT_FOLDER = 'attachments';

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Upload an UploadedFile to Azure Blob Storage.
     *
     * Images are compressed to JPEG before upload to reduce storage cost.
     * Other files are streamed directly without modification.
     *
     * @param  UploadedFile  $file
     * @param  string        $folder  Sub-folder inside the container
     * @return array{ url: string, path: string }
     */
    public function uploadFile(UploadedFile $file, string $folder = self::DEFAULT_FOLDER): array
    {
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = $this->uniqueFilename($ext);
        $blobPath = $folder . '/' . $filename;

        if ($this->isImageExtension($ext)) {
            // Compress to JPEG and upload raw bytes
            $contents = $this->compressToJpeg($file->getRealPath());

            Storage::disk('azure')->put($blobPath, $contents, [
                'Content-Type' => 'image/jpeg',
            ]);

        } else {
            // Stream the file directly to Azure without loading it into memory
            $fileStream = fopen($file->getRealPath(), 'r');

            Storage::disk('azure')->put($blobPath, $fileStream, [
                'Content-Type' => $file->getMimeType(),
            ]);

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }
        }

        return [
            'url'  => $this->publicUrl($blobPath),
            'path' => $blobPath,
        ];
    }

    /**
     * Decode a base64 data-URI or raw base64 string and upload to Azure.
     *
     * Accepts both formats:
     *   "data:image/png;base64,iVBORw0KG..."   ← data-URI
     *   "iVBORw0KG..."                          ← raw base64
     *
     * @param  string  $base64  Raw base64 or data-URI string
     * @param  string  $ext     File extension to use (e.g. 'png', 'jpg')
     * @param  string  $folder  Sub-folder inside the container
     * @return array{ url: string, path: string }
     * @throws \InvalidArgumentException  If the base64 string is malformed.
     */
    public function uploadBase64(
        string $base64,
        string $ext    = 'png',
        string $folder = self::DEFAULT_FOLDER
    ): array {
        // Strip data-URI prefix if present (e.g. "data:image/png;base64,")
        if (str_contains($base64, ';base64,')) {
            [, $base64] = explode(';base64,', $base64, 2);
        }

        $contents = base64_decode($base64, strict: true);

        if ($contents === false) {
            throw new \InvalidArgumentException('Invalid base64 string provided.');
        }

        $ext      = strtolower(ltrim($ext, '.'));
        $mimeType = $this->extToMime($ext);
        $blobPath = $folder . '/' . $this->uniqueFilename($ext);

        Storage::disk('azure')->put($blobPath, $contents, [
            'Content-Type' => $mimeType,
        ]);

        return [
            'url'  => $this->publicUrl($blobPath),
            'path' => $blobPath,
        ];
    }

    /**
     * Delete a blob by its path (relative to the container root).
     * Silently swallows errors so a missing blob never breaks a delete flow.
     *
     * @param  string  $blobPath  e.g. "attachments/uuid_1234.jpg"
     */
    public function delete(string $blobPath): void
    {
        try {
            Storage::disk('azure')->delete($blobPath);
        } catch (\Throwable) {
            // Non-fatal — blob may have already been removed externally
        }
    }

    /**
     * Return the full public URL for a stored blob path.
     *
     * @param  string  $blobPath  e.g. "attachments/uuid_1234.jpg"
     * @return string             e.g. "https://devhubblobs.blob.core.windows.net/chat-files/attachments/uuid_1234.jpg"
     */
    public function publicUrl(string $blobPath): string
    {
        return Storage::disk('azure')->url($blobPath);
    }

    // =========================================================================
    // Legacy compatibility shim
    // =========================================================================

    /**
     * @deprecated  Use uploadFile() for new code.
     *
     * Kept so that existing callers (e.g. ImageUploadCloudinaryService swap-outs)
     * continue to work without immediate refactoring.
     *
     * Returns only the URL string (not the full array) to preserve the old contract.
     */
    public function uploadImage(UploadedFile $file, string $path = self::DEFAULT_FOLDER): string
    {
        // uploadFile() now returns array{ url, path } — extract the URL only
        // so legacy callers that expect a plain string are not broken.
        return $this->uploadFile($file, $path)['url'];
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Generate a unique filename with a UUID prefix to prevent collisions.
     */
    private function uniqueFilename(string $ext): string
    {
        return Str::uuid() . '_' . time() . '.' . $ext;
    }

    /**
     * Check whether the given extension belongs to an image format.
     * These files are compressed to JPEG before upload.
     */
    private function isImageExtension(string $ext): bool
    {
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    /**
     * Compress an image to JPEG at the configured quality level.
     *
     * Uses PHP's GD extension. Falls back to the original raw bytes if GD
     * cannot parse the format (e.g. animated GIF, corrupt file).
     *
     * @param  string  $sourcePath  Absolute path to the source image file
     * @return string               Raw binary JPEG bytes
     */
    private function compressToJpeg(string $sourcePath): string
    {
        $image = @imagecreatefromstring(file_get_contents($sourcePath));

        if ($image === false) {
            // GD cannot parse this format — upload the original bytes unchanged
            return file_get_contents($sourcePath);
        }

        ob_start();
        imagejpeg($image, null, self::JPEG_QUALITY);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
    }

    /**
     * Map a file extension to its canonical MIME type.
     * Falls back to application/octet-stream for unknown extensions.
     */
    private function extToMime(string $ext): string
    {
        return match($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'pdf'         => 'application/pdf',
            'txt'         => 'text/plain',
            'doc'         => 'application/msword',
            'docx'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default       => 'application/octet-stream',
        };
    }
}
