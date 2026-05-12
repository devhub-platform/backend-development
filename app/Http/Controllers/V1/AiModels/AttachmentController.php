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
        protected IngestionService        $ingestion,
        protected AzureBlobStorageService $azure,
    ) {}

    /**
     * Upload a file to Azure Blob Storage and trigger text extraction.
     *
     * Images are stored as-is (with compression) and their public URL is
     * returned directly — no text extraction is needed.
     *
     * Documents (PDF, DOCX, TXT) are text-extracted immediately at upload
     * time so the first chat message referencing them is never delayed.
     *
     * Max file size: 200 MB
     * Allowed types: pdf, txt, doc, docx, png, jpeg, jpg, gif
     */
    public function upload(Request $request): JsonResponse
    {
        $allowedExtensions = ['pdf', 'txt', 'doc', 'docx', 'png', 'jpeg', 'jpg', 'gif'];
        $allowedRealMimes  = [
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/png',
            'image/jpeg',
            'image/gif',
        ];

        // ── Validate: file present ────────────────────────────────────────────
        if (!$request->hasFile('file')) {
            return response()->json([
                'error'   => 'No file provided',
                'message' => 'Please select a file to upload',
            ], 400);
        }

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        // ── Validate: allowed extension ───────────────────────────────────────
        if (!in_array($ext, $allowedExtensions)) {
            return response()->json([
                'error'   => 'Invalid file type',
                'message' => 'Allowed types: ' . implode(', ', $allowedExtensions),
            ], 400);
        }

        // ── Validate: real MIME type (prevents .exe renamed to .pdf) ──────────
        $realMime = $file->getMimeType();
        if (!in_array($realMime, $allowedRealMimes)) {
            return response()->json([
                'error'   => 'Invalid file content',
                'message' => 'File content does not match the declared file type.',
            ], 400);
        }

        // ── Validate: file size (max 200 MB) ──────────────────────────────────
        $maxBytes = 200 * 1024 * 1024; // 200 MB
        if ($file->getSize() > $maxBytes) {
            return response()->json([
                'error'   => 'File too large',
                'message' => 'Maximum allowed size is 200 MB',
            ], 400);
        }

        $isImage = $this->isImage($ext);

        // ── Upload to Azure Blob Storage ──────────────────────────────────────
        try {
            // uploadFile() returns ['url' => '...', 'path' => '...']
            $result   = $this->azure->uploadFile($file, 'attachments');
            $url      = $result['url'];
            $blobPath = $result['path'];
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Upload failed',
                'message' => 'Could not upload file. Please try again.',
            ], 500);
        }

        // ── Persist the attachment record ─────────────────────────────────────
        $attachment = Attachment::create([
            'url'        => $url,
            'blob_path'  => $blobPath,
            'filename'   => $file->getClientOriginalName(),
            'mime_type'  => $realMime,
            'size'       => $file->getSize(),
            'type'       => $isImage ? 'image' : 'document',
            // Images are immediately usable; documents need text extraction first.
            'status'     => $isImage ? 'processed' : 'pending',
            'extension'  => $ext,
            'text'       => null,
            'user_id'    => $request->user()->id,
            'session_id' => $request->input('session_id'),
        ]);

        // ── Extract text from documents (synchronous, non-blocking for images) ─
        if (!$isImage) {
            try {
                $this->ingestion->extractAndStore($attachment);
            } catch (\Exception) {
                // Non-fatal — chat layer will surface a graceful error message
                // if the text is unavailable when the user sends a message.
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
     * Delete an attachment and its corresponding Azure Blob object.
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

        // Delete the blob from Azure; non-fatal if it is already gone.
        $blobPath = $attachment->blob_path ?? $attachment->s3_path ?? null;
        if ($blobPath) {
            $this->azure->delete($blobPath);
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

    // =========================================================================
    // Helpers
    // =========================================================================

    private function isImage(string $ext): bool
    {
        return in_array($ext, ['png', 'jpeg', 'jpg', 'gif']);
    }
}
