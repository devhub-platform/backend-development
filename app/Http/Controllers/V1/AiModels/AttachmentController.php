<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\AzureBlobStorageService;
use App\Services\Chat\IngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function __construct(
        protected IngestionService $ingestion,
        protected AzureBlobStorageService $azure,
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $allowedExtensions = ['pdf', 'txt', 'doc', 'docx', 'png', 'jpeg', 'jpg', 'gif'];
        $allowedRealMimes  = [
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/png', 'image/jpeg', 'image/gif',
        ];

        if (!$request->hasFile('file')) {
            return response()->json([
                'error' => 'No file provided',
                'message' => 'Please select a file to upload'
            ], 400);
        }

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedExtensions)) {
            return response()->json([
                'error' => 'Invalid file type',
                'message' => 'Allowed types: ' . implode(', ', $allowedExtensions),
            ], 400);
        }

        $realMime = $file->getMimeType();
        if (!in_array($realMime, $allowedRealMimes)) {
            return response()->json([
                'error' => 'Invalid file content',
                'message' => 'File content does not match the declared file type.',
            ], 400);
        }

        if ($file->getSize() / 1024 > 102400) {
            return response()->json([
                'error' => 'File too large',
                'message' => 'Maximum allowed size is 100 MB'
            ], 400);
        }

        $isImage = $this->isImage($ext);

        // ── Upload to Azure ─────────────────────────────
        try {
            $result = $this->azure->uploadFile($file, 'attachments');

            $url      = $result['url'];
            $blobPath = $result['path'];

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage()
            ], 500);
        }

        $attachment = Attachment::create([
            'url'        => $url,
            'blob_path'  => $blobPath,
            'filename'   => $file->getClientOriginalName(),
            'mime_type'  => $realMime,
            'size'       => $file->getSize(),
            'type'       => $isImage ? 'image' : 'document',
            'status'     => $isImage ? 'processed' : 'pending',
            'extension'  => $ext,
            'text'       => null,
            'user_id'    => $request->user()->id,
            'session_id' => $request->input('session_id'),
        ]);

        if (!$isImage) {
            try {
                $this->ingestion->extractAndStore($attachment);
            } catch (\Exception) {
                // ignore
            }
        }

        return response()->json([
            'attachment_id' => $attachment->id,
            'url'           => $url,
            'filename'      => $file->getClientOriginalName(),
            'mime_type'     => $realMime,
            'type'          => $isImage ? 'image' : 'document',
            'status'        => $attachment->fresh()->status,
        ], 201);
    }

    private function isImage(string $ext): bool
    {
        return in_array($ext, ['png', 'jpeg', 'jpg', 'gif']);
    }
}
