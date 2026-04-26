<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AzureBlobStorageService
{
    const COMPRESSION_RATIO = 20;

    public function uploadImage($file, $path)
    {
        $file = $this->compressImage($file);
        $fileName = uniqid() . Str::random(5) . time() . '.' . $file->getClientOriginalExtension();
        $contentType = $file->getClientMimeType();
        $options = ['Content-Type' => $contentType];
        Storage::disk('azure')->putFileAs($path, $file, $fileName, $options);
        return Storage::disk('azure')->url("$path/$fileName");
    }

    private function compressImage($file)
    {
        $originalImage = imagecreatefromstring(file_get_contents($file->path()));
        $compressionQuality = self::COMPRESSION_RATIO;
        ob_start();
        imagejpeg($originalImage, null, $compressionQuality);
        $compressedImageData = ob_get_contents();
        ob_end_clean();
        $tempFilePath = tempnam(sys_get_temp_dir(), 'compressed_image');
        file_put_contents($tempFilePath, $compressedImageData);
        $compressedFile = new \Illuminate\Http\UploadedFile(
            $tempFilePath,
            $file->getClientOriginalName(),
            $file->getClientMimeType()
        );
        imagedestroy($originalImage);
        return $compressedFile;
    }
}
