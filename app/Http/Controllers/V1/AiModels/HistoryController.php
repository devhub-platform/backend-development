<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use App\Models\AIChatMessage;
use App\Models\AIChatSession;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HistoryController extends Controller
{
    public function sessions(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $sessions = AIChatSession::withCount('messages')
            ->where('user_id', $userId)
            ->orderBy('pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate($request->query('per_page', 20));

        // Build model title lookup from config
        $modelTitles = collect(config('ai_models.chat', []))->keyBy('id');

        return response()->json([
            'sessions' => $sessions->map(fn($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'model' => $s->model,
                'model_title' => $modelTitles->get($s->model)['title'] ?? $s->model,
                'message_count' => $s->messages_count,
                'created_at' => $s->created_at,
                'updated_at' => $s->updated_at,
                'pinned' => (bool)$s->pinned,
                'active' => (bool)$s->active,
            ]),
            'pagination' => [
                'total' => $sessions->total(),
                'per_page' => $sessions->perPage(),
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        return response()->json([
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'model' => $session->model,
                'pinned' => (bool)$session->pinned,
                'created_at' => $session->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $session->updated_at->format('Y-m-d H:i:s'),
            ],
            'messages' => $session->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($m) => [
                    'id' => $m->id,
                    'role' => $m->role,
                    'content' => $m->content,
                    'attachments' => $m->attachments ?? [],
                    'created_at' => $m->created_at->format('Y-m-d H:i:s'),
                ]),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'model' => 'nullable|string',
        ]);

        $session = AIChatSession::create([
            'user_id' => $request->user()?->id,
            'title' => $request->title ?? 'New Chat',
            'model' => $request->model ?? config('ai_models.default'),
            'active' => true,
            'pinned' => false,
        ]);

        return response()->json([
            'id' => $session->id,
            'title' => $session->title,
            'model' => $session->model,
            'created_at' => $session->created_at->format('Y-m-d H:i:s'),
        ], 201);
    }

    public function delete(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        $messages = AIChatMessage::where('ai_chat_session_id', $sessionId)->get();
        $attachmentIds = $messages
            ->flatMap(fn($m) => $m->attachments ?? [])
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (!empty($attachmentIds)) {
            $attachments = Attachment::whereIn('id', $attachmentIds)
                ->where('user_id', $request->user()->id)
                ->get();

            foreach ($attachments as $attachment) {
                if ($attachment->s3_path) {
                    try {
                        Storage::disk('s3')->delete($attachment->s3_path);
                    } catch (\Exception) {
                        // Non-fatal — continue cleanup.
                    }
                }
            }

            Attachment::whereIn('id', $attachmentIds)
                ->where('user_id', $request->user()->id)
                ->delete();
        }

        $messages->each->delete();
        $session->delete();

        return response()->json(['message' => 'Session deleted successfully']);
    }

    public function pin(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        $session->update(['pinned' => true]);

        return response()->json(['message' => 'Session pinned', 'pinned' => true]);
    }

    public function unpin(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        $session->update(['pinned' => false]);

        return response()->json(['message' => 'Session unpinned', 'pinned' => false]);
    }

    public function updateTitle(Request $request, $sessionId): JsonResponse
    {
        $request->validate(['title' => 'required|string|max:255']);

        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        $session->update(['title' => $request->title]);

        return response()->json(['message' => 'Title updated', 'title' => $session->title]);
    }

    // -------------------------------------------------------------------------

    private function findUserSession($sessionId, ?int $userId): ?AIChatSession
    {
        if (!$userId) {
            return null;
        }

        return AIChatSession::where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Session not found'], 404);
    }
}
