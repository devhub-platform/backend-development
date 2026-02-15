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
    protected IngestionService $ingestion;

    public function __construct(IngestionService $ingestion)
    {
        $this->ingestion = $ingestion;
    }

    public function upload(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json([
                'error' => 'Authentication required',
                'message' => 'Please login to upload files'
            ], 401);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,txt,doc,docx,png,jepg,gif|max:10240',
            'session_id' => 'nullable|exists:ai_chat_sessions,id'
        ]);

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('chat-attachments', $filename, 's3');
        $url = Storage::disk('s3')->url($path);
        $text = $this->ingestion->extractText($file);

        $attachment = Attachment::create([
            'url' => $url,
            'text' => $text,
            'filename' => $file->getClientOriginalName(),
            'user_id' => $request->user()->id,
            'session_id' => $request->session_id
        ]);

        return response()->json([
            'url' => $url,
            'attachment_id' => $attachment->id,
            'filename' => $file->getClientOriginalName()
        ]);
    }
}
