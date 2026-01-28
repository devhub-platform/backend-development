<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\AIChatSession;
use App\Models\AIChatMessage;
use App\Services\HackAIChatService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Exception;

class AIChatController extends Controller
{
    protected HackAIChatService $ai;

    public function __construct(HackAIChatService $ai)
    {
        $this->ai = $ai;
    }

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'nullable|exists:ai_chat_sessions,id',
            'message'    => 'required|string',
            'model'      => 'required|string',
            'attachments.*' =>
                'nullable|file|mimes:jpg,jpeg,png,gif,pdf,txt,doc,docx|max:5120',
        ]);

        if (!$this->ai->isValidModel($request->model)) {
            throw new HttpResponseException(response()->json([
                'error' => 'Invalid AI model'
            ], 422));
        }

        try {
            /* =====================
             |  Session handling
             ===================== */
            $session = $request->session_id
                ? AIChatSession::findOrFail($request->session_id)
                : AIChatSession::create([
                    'user_id' => $request->user()?->id,
                    'model'   => $request->model,
                ]);

            /* =====================
             |  Upload attachments
             ===================== */
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('chat-attachments', $filename, 's3');
                    $attachments[] = Storage::disk('s3')->url($path);
                }
            }

            /* =====================
             |  Save user message
             ===================== */
            AIChatMessage::create([
                'ai_chat_session_id' => $session->id,
                'role'        => 'user',
                'content'     => $request->message,
                'attachments' => $attachments,
            ]);

            /* =====================
             |  Load full history
             ===================== */
            $messages = AIChatMessage::where('ai_chat_session_id', $session->id)
                ->orderBy('id')
                ->get(['role', 'content'])
                ->map(fn ($m) => [
                    'role'    => $m->role,
                    'content' => $m->content,
                ])
                ->toArray();

            /* =====================
             |  Call AI
             ===================== */
            $response = $this->ai->chat($messages, $request->model);

            $body = json_decode($response->getBody()->getContents(), true);

            /* =====================
             |  Defensive parsing
             ===================== */
            $aiContent =
                $body['choices'][0]['message']['content']
                ?? $body['choices'][0]['text']
                ?? $body['content']
                ?? $body['response']
                ?? null;

            if (!$aiContent) {
                throw new Exception('Invalid AI response format');
            }

            /* =====================
             |  Save AI message
             ===================== */
            $aiMessage = AIChatMessage::create([
                'ai_chat_session_id' => $session->id,
                'role'    => 'assistant',
                'content' => $aiContent,
                'attachments' => [],
            ]);

            return response()->json([
                'session_id'       => $session->id,
                'user_message'     => $request->message,
                'user_attachments' => $attachments,
                'ai_message'       => $aiMessage->content,
            ]);

        } catch (HttpResponseException $e) {
            throw $e;
        } catch (Exception $e) {
            return response()->json([
                'error'   => 'AI Chat Error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function models(): JsonResponse
    {
        return response()->json([
            'models' => $this->ai->getAvailableModels(),
        ]);
    }
}
