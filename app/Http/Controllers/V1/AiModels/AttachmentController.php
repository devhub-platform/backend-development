<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Services\Chat\IngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function __construct(
        protected IngestionService $ingestion,
    ) {}

    /**
     * Upload a file, store it on S3, and trigger text extraction immediately.
     *
     * Extraction happens here at upload time — not during the chat request —
     * so the first message with an attachment is never delayed by heavy I/O.
     */
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
            return response()->json(['error' => 'No file provided', 'message' => 'Please select a file to upload'], 400);
        }

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedExtensions)) {
            return response()->json([
                'error'   => 'Invalid file type',
                'message' => 'Allowed types: ' . implode(', ', $allowedExtensions),
            ], 400);
        }

        // Validate the real MIME type, not just the extension.
        // Prevents disguised uploads such as an .exe renamed to .pdf.
        $realMime = $file->getMimeType();
        if (!in_array($realMime, $allowedRealMimes)) {
            return response()->json([
                'error'   => 'Invalid file content',
                'message' => 'File content does not match the declared file type.',
            ], 400);
        }

        if ($file->getSize() / 1024 > 102400) {
            return response()->json(['error' => 'File too large', 'message' => 'Maximum allowed size is 100 MB'], 400);
        }

        $isImage  = $this->isImage($ext);
        $filename = Str::uuid() . '.' . $ext;
        $s3Path   = 'chat-attachments/' . $filename;

        try {
            Storage::disk('s3')->putFileAs('chat-attachments', $file, $filename);
        } catch (\Exception) {
            return response()->json(['error' => 'Upload failed', 'message' => 'Could not upload file. Please try again.'], 500);
        }

        // Generate a short-lived presigned URL so the response works even with private buckets.
        try {
            $url = Storage::disk('s3')->temporaryUrl($s3Path, now()->addMinutes(10));
        } catch (\Exception) {
            $url = Storage::disk('s3')->url($s3Path);
        }

        $attachment = Attachment::create([
            'url'        => $url,
            's3_path'    => $s3Path,
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
                // Non-fatal — the attachment is saved; the chat layer will surface
                // a graceful "could not extract" message if needed.
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

    /**
     * Delete an attachment and its corresponding S3 object.
     * Only the owning user may delete their own attachments.
     */
    public function destroy(Request $request, int $attachmentId): JsonResponse
    {
        $attachment = Attachment::where('id', $attachmentId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$attachment) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        if ($attachment->s3_path) {
            try {
                Storage::disk('s3')->delete($attachment->s3_path);
            } catch (\Exception) {
                // Non-fatal — remove the DB record regardless.
            }
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully']);
    }

    /**
     * Return the current extraction status of an attachment.
     * The frontend polls this endpoint to know when a document is ready.
     *
     * @return JsonResponse  { attachment_id, status: pending|processed|failed, type, filename }
     */
    public function status(Request $request, int $attachmentId): JsonResponse
    {
        $attachment = Attachment::where('id', $attachmentId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$attachment) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        return response()->json([
            'attachment_id' => $attachment->id,
            'status'        => $attachment->status,
            'type'          => $attachment->type,
            'filename'      => $attachment->filename,
        ]);
    }

    private function isImage(string $ext): bool
    {
        return in_array($ext, ['png', 'jpeg', 'jpg', 'gif']);
    }
}
