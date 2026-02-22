<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Chat\IngestionService;
use App\Models\Attachment;

class AttachmentController extends Controller
{
    public function __construct(protected IngestionService $ingestion) {}

    public function upload(Request $request): JsonResponse
    {
        set_time_limit(500);
        if (!$request->user()) {
            return response()->json([
                'error'   => 'Authentication required',
                'message' => 'Please login to upload files',
            ], 401);
        }

        $request->validate([
            'file'       => 'required|file|mimes:pdf,txt,doc,docx,png,jpeg,jpg,gif|max:10240',
            'session_id' => 'nullable|exists:ai_chat_sessions,id',
        ]);

        $file     = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('chat-attachments', $filename, 's3');
        $url      = Storage::disk('s3')->url($path);
        $isImage  = $this->isImage($file->getClientOriginalExtension());

        // For images: store base64. For documents: extract text
        $text    = null;
        $base64  = null;

        if ($isImage) {
            $base64 = base64_encode(file_get_contents($file->getPathname()));
        } else {
            $text = $this->ingestion->extractText($file);
        }

        $attachment = Attachment::create([
            'url'        => $url,
            'text'       => $text,
            'base64'     => $base64,
            'mime_type'  => $file->getMimeType(),
            'filename'   => $file->getClientOriginalName(),
            'user_id'    => $request->user()->id,
            'session_id' => $request->session_id,
        ]);

        return response()->json([
            'url'           => $url,
            'attachment_id' => $attachment->id,
            'filename'      => $file->getClientOriginalName(),
            'type'          => $isImage ? 'image' : 'document',
            'has_text'      => !empty($text),
            'has_image'     => !empty($base64),
        ]);
    }

    private function isImage(string $ext): bool
    {
        return in_array(strtolower($ext), ['png', 'jpeg', 'jpg', 'gif']);
    }
}
