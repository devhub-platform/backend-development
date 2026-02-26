<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\V1\Controller;
use App\Jobs\ProcessAttachmentJob;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'error'   => 'Authentication required',
                'message' => 'Please login to upload files',
            ], 401);
        }

        // Define allowed types and max size (100 MB)
        $allowedMimes = ['pdf','txt','doc','docx','png','jpeg','jpg','gif'];
        $maxSizeKB    = 102400; // 100 MB

        // Check if file is provided
        if (!$request->hasFile('file')) {
            return response()->json([
                'error'   => 'No file provided',
                'message' => 'Please select a file to upload',
            ], 400);
        }

        $file = $request->file('file');

        // Check MIME type
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $allowedMimes)) {
            return response()->json([
                'error'   => 'Invalid file type',
                'message' => "Allowed types: " . implode(', ', $allowedMimes),
            ], 400);
        }

        // Check file size
        $sizeKB = $file->getSize() / 1024;
        if ($sizeKB > $maxSizeKB) {
            return response()->json([
                'error'   => 'File too large',
                'message' => "Maximum allowed size is {$maxSizeKB} KB",
            ], 400);
        }

        $isImage  = $this->isImage($ext);
        $filename = Str::uuid() . '.' . $ext;
        $s3Path   = 'chat-attachments/' . $filename;

        // Upload file safely to S3
        Storage::disk('s3')->putFileAs('chat-attachments', $file, $filename);
        $url = Storage::disk('s3')->url($s3Path);

        // Save metadata to DB
        $attachment = Attachment::create([
            'url'        => $url,
            'filename'   => $file->getClientOriginalName(),
            'mime_type'  => $file->getMimeType(),
            'size'       => $file->getSize(),
            'type'       => $isImage ? 'image' : 'document',
            'status'     => $isImage ? 'processed' : 'pending',
            'text'       => null,
            'user_id'    => $request->user()->id,
            'session_id' => $request->session_id,
        ]);

        // Dispatch background job for documents only
        if (!$isImage) {
            ProcessAttachmentJob::dispatch(
                $attachment->id,
                $s3Path,
                $ext
            );
        }

        return response()->json([
            'attachment_id' => $attachment->id,
            'url'           => $url,
            'filename'      => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'type'          => $isImage ? 'image' : 'document',
            'status'        => $attachment->status,
        ], 201);
    }

    private function isImage(string $ext): bool
    {
        return in_array($ext, ['png','jpeg','jpg','gif']);
    }
}
