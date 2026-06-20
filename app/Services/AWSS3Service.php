<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AWSS3Service
{
    /**
     * Upload a file to S3 and return the full URL.
     *
     * @param  UploadedFile  $file
     * @param  string  $folder
     * @param  string|null  $customName
     * @return string
     */
    public function uploadFile(UploadedFile $file, string $folder, ?string $customName = null): string
    {
        $fileName = $customName ?? Str::uuid()->toString();
        $path = $folder . '/' . $fileName . '_' . time() . '.' . $file->getClientOriginalExtension();

        Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()), 'public');

        return Storage::disk('s3')->url($path);
    }

    /**
     * Delete a file from S3.
     *
     * @param  string  $url
     * @return bool
     */
    public function deleteFile(string $url): bool
    {
        $path = $this->getPathFromUrl($url);

        if (!$path) {
            return false;
        }

        return Storage::disk('s3')->delete($path);
    }

    /**
     * Extract the path from the S3 URL.
     *
     * @param  string  $url
     * @return string|null
     */
    private function getPathFromUrl(string $url): ?string
    {
        $bucket = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');

        $pattern = "/https:\/\/.*\.s3\..*\.amazonaws\.com\/(.+)/";

        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
