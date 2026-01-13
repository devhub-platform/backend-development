<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Chat\ChatService;

class AIChatController extends Controller
{
    protected ChatService $chat;

    public function __construct(ChatService $chat)
    {
        $this->chat = $chat;
    }

    public function chat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'    => 'nullable|exists:ai_chat_sessions,id',
            'model'         => 'required|string',
            'message'       => 'required|string',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,txt,doc,docx|max:5120',
        ]);

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('chat-attachments', $filename, 's3');
                $attachments[] = Storage::disk('s3')->url($path);
            }
        }

        $data['attachments'] = $attachments;

        $response = $this->chat->handle($data, $request->user());

        return response()->json([
            'session_id'       => $response['session_id'],
            'user_message'     => $data['message'],
            'user_attachments' => $attachments,
            'ai_message'       => $response['content'],
        ]);
    }

    public function models(): JsonResponse
    {
        return response()->json([
            'models' => config('ai_models.chat'),
        ]);
    }
}
