<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\AIChatSession;
use App\Models\AIChatMessage;

class HistoryController extends Controller
{
    public function sessions(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        // Require authentication - never expose all sessions to guests
        if (!$userId) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $sessions = AIChatSession::with(['messages' => fn($q) => $q->latest()->take(1)])
            ->withCount('messages')
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->paginate($request->query('per_page', 20));

        return response()->json([
            'sessions'   => $sessions->map(fn($s) => [
                'id'            => $s->id,
                'title'         => $s->title,
                'model'         => $s->model,
                'message_count' => $s->messages_count,
                'last_message'  => $s->messages->first()?->content,
                'created_at'    => $s->created_at,
                'updated_at'    => $s->updated_at,
                'pinned'        => (bool) $s->pinned,
                'active'        => (bool) $s->active,
            ]),
            'pagination' => [
                'total'        => $sessions->total(),
                'per_page'     => $sessions->perPage(),
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) return $this->notFound();

        return response()->json([
            'session'  => [
                'id'         => $session->id,
                'title'      => $session->title,
                'model'      => $session->model,
                'created_at' => $session->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $session->updated_at->format('Y-m-d H:i:s'),
            ],
            'messages' => $session->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($m) => [
                    'id'          => $m->id,
                    'role'        => $m->role,
                    'content'     => $m->content,
                    'attachments' => $m->attachments ?? [],
                    'created_at'  => $m->created_at->format('Y-m-d H:i:s'),
                ]),
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'model' => 'required|string',
        ]);

        $session = AIChatSession::create([
            'user_id' => $request->user()?->id,
            'title'   => $request->title ?? 'New Chat',
            'model'   => $request->model,
            'active'  => true,
            'pinned'  => false,
        ]);

        return response()->json([
            'id'         => $session->id,
            'title'      => $session->title,
            'model'      => $session->model,
            'created_at' => $session->created_at->format('Y-m-d H:i:s'),
        ], 201);
    }

    public function delete(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) return $this->notFound();

        // Delete all messages before deleting the session
        AIChatMessage::where('ai_chat_session_id', $sessionId)->delete();
        $session->delete();

        return response()->json(['message' => 'Session deleted successfully']);
    }

    public function pin(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) return $this->notFound();

        $session->update(['pinned' => true]);
        return response()->json(['message' => 'Session pinned', 'pinned' => true]);
    }

    public function unpin(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) return $this->notFound();

        $session->update(['pinned' => false]);
        return response()->json(['message' => 'Session unpinned', 'pinned' => false]);
    }

    public function close(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) return $this->notFound();

        $session->update(['active' => false, 'closed_at' => now()]);

        return response()->json([
            'message'   => 'Session closed',
            'active'    => false,
            'closed_at' => $session->closed_at,
        ]);
    }

    public function activate(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) return $this->notFound();

        $session->update(['active' => true, 'closed_at' => null]);
        return response()->json(['message' => 'Session activated', 'active' => true]);
    }

    public function updateTitle(Request $request, $sessionId): JsonResponse
    {
        $request->validate(['title' => 'required|string|max:255']);

        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) return $this->notFound();

        $session->update(['title' => $request->title]);
        return response()->json(['message' => 'Title updated', 'title' => $session->title]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Find a session that belongs to the given user.
     * Returns null if not found or user is not authenticated.
     */
    private function findUserSession($sessionId, ?int $userId): ?AIChatSession
    {
        if (!$userId) return null;

        return AIChatSession::where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Session not found'], 404);
    }
}
