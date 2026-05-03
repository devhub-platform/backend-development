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
 *  - Raw base64-encoded strings
 *  - Optional JPEG compression for images
 *
 * All files land under  {container}/{$folder}/{filename}
 * and are publicly readable (container must have anonymous blob read access).
 */
class AzureBlobStorageService
{
    /** JPEG quality 1-100 used when compressing images before upload */
    private const JPEG_QUALITY = 75;

    /** Base folder inside the container for chat attachments */
    private const DEFAULT_FOLDER = 'attachments';

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Upload an UploadedFile, compress it if it is an image, and return the
     * public Azure Blob URL.
     *
     * @param  UploadedFile  $file
     * @param  string        $folder  Sub-folder inside the container
     * @return string                 Public URL
     */
    public function uploadFile(UploadedFile $file, string $folder = self::DEFAULT_FOLDER): array
    {
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = $this->uniqueFilename($ext);
        $blobPath = $folder . '/' . $filename;

        if ($this->isImageExtension($ext)) {

            $contents = $this->compressToJpeg($file->getRealPath());

            Storage::disk('azure')->put($blobPath, $contents, [
                'Content-Type' => 'image/jpeg',
            ]);

        } else {

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
     * Decode a base64 data-URI or raw base64 string, upload to Azure, and
     * return the public URL.
     *
     * Accepts both formats:
     *   "data:image/png;base64,iVBORw0KG..."
     *   "iVBORw0KG..."
     *
     * @param  string  $base64    Raw base64 or data-URI string
     * @param  string  $ext       File extension to use (e.g. 'png', 'jpg')
     * @param  string  $folder    Sub-folder inside the container
     * @return string             Public URL
     */
    public function uploadBase64(
        string $base64,
        string $ext    = 'png',
        string $folder = self::DEFAULT_FOLDER
    ): string {
        // Strip data-URI prefix if present
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
            'visibility'   => 'public',
            'Content-Type' => $mimeType,
        ]);

        return $this->publicUrl($blobPath);
    }

    /**
     * Delete a blob by its path (relative to the container root).
     * Silently swallows errors so a missing file never breaks a delete flow.
     */
    public function delete(string $blobPath): void
    {
        try {
            Storage::disk('azure')->delete($blobPath);
        } catch (\Throwable) {
            // Non-fatal
        }
    }

    /**
     * Return the full public URL for a stored blob path.
     */
    public function publicUrl(string $blobPath): string
    {
        return Storage::disk('azure')->url($blobPath);
    }

    // -------------------------------------------------------------------------
    // Legacy compatibility shim
    // -------------------------------------------------------------------------

    /**
     * @deprecated  Use uploadFile() for new code.
     *              Kept so that existing callers (e.g. ImageUploadCloudinaryService
     *              swap-outs) continue to work without immediate refactoring.
     */
    public function uploadImage(UploadedFile $file, string $path = self::DEFAULT_FOLDER): string
    {
        return $this->uploadFile($file, $path);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function uniqueFilename(string $ext): string
    {
        return Str::uuid() . '_' . time() . '.' . $ext;
    }

    private function isImageExtension(string $ext): bool
    {
        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    /**
     * Compress an image file to JPEG at the configured quality.
     * Returns the raw binary string ready to be written to Azure.
     */
    private function compressToJpeg(string $sourcePath): string
    {
        $image = @imagecreatefromstring(file_get_contents($sourcePath));

        if ($image === false) {
            // If GD cannot parse it (e.g. GIF), just return the raw file bytes
            return file_get_contents($sourcePath);
        }

        ob_start();
        imagejpeg($image, null, self::JPEG_QUALITY);
        $data = ob_get_clean();
        imagedestroy($image);

        return $data;
    }

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
