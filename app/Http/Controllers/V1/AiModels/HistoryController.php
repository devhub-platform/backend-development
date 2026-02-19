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

        $sessions = AIChatSession::with(['messages' => function($query) {
            $query->latest()->take(1);
        }])
            ->withCount('messages')
            ->when($userId, function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($request->query('per_page', 20));

        return response()->json([
            'sessions' => $sessions->map(function($session) {
                return [
                    'id' => $session->id,
                    'title' => $session->title,
                    'model' => $session->model,
                    'message_count' => $session->messages_count,
                    'last_message' => $session->messages->first()?->content,
                    'created_at' => $session->created_at,
                    'updated_at' => $session->updated_at,
                    'pinned' => (bool) $session->pinned,
                    'active' => (bool) $session->active
                ];
            }),
            'pagination' => [
                'total' => $sessions->total(),
                'per_page' => $sessions->perPage(),
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage()
            ]
        ]);
    }

    public function show(Request $request, $sessionId): JsonResponse
    {
        $userId = $request->user()?->id;

        $session = AIChatSession::when($userId, function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->with(['messages' => function($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->findOrFail($sessionId);

        return response()->json([
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'model' => $session->model,
                'created_at' => $session->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $session->updated_at->format('Y-m-d H:i:s'),
            ],
            'messages' => $session->messages->map(function($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'attachments' => $message->attachments ?? [],
                    'created_at' => $message->created_at->format('Y-m-d H:i:s')
                ];
            })
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'model' => 'required|string'
        ]);

        $session = AIChatSession::create([
            'user_id' => $request->user()?->id,
            'title' => $request->title ?? 'New Chat',
            'model' => $request->model,
            'active' => true,
            'pinned' => false
        ]);

        return response()->json([
            'id' => $session->id,
            'title' => $session->title,
            'model' => $session->model,
            'created_at' => $session->created_at->format('Y-m-d H:i:s'),
        ], 201);
    }

    public function delete($sessionId): JsonResponse
    {
        $session = AIChatSession::findOrFail($sessionId);

        AIChatMessage::where('ai_chat_session_id', $sessionId)->delete();
        $session->delete();

        return response()->json([
            'message' => 'Session deleted successfully'
        ]);
    }

    public function pin($sessionId): JsonResponse
    {
        $session = AIChatSession::findOrFail($sessionId);
        $session->update(['pinned' => true]);

        return response()->json([
            'message' => 'Session pinned',
            'pinned' => true
        ]);
    }

    public function unpin($sessionId): JsonResponse
    {
        $session = AIChatSession::findOrFail($sessionId);
        $session->update(['pinned' => false]);

        return response()->json([
            'message' => 'Session unpinned',
            'pinned' => false
        ]);
    }

    public function close($sessionId): JsonResponse
    {
        $session = AIChatSession::findOrFail($sessionId);
        $session->update([
            'active' => false,
            'closed_at' => now()
        ]);

        return response()->json([
            'message' => 'Session closed',
            'active' => false,
            'closed_at' => $session->closed_at
        ]);
    }

    public function activate($sessionId): JsonResponse
    {
        $session = AIChatSession::findOrFail($sessionId);
        $session->update([
            'active' => true,
            'closed_at' => null
        ]);

        return response()->json([
            'message' => 'Session activated',
            'active' => true
        ]);
    }

    public function updateTitle(Request $request, $sessionId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $userId = $request->user()?->id;

        $session = AIChatSession::when($userId, function($query) use ($userId) {
            $query->where('user_id', $userId);
        })->findOrFail($sessionId);

        $session->update(['title' => $request->title]);

        return response()->json([
            'message' => 'Title updated',
            'title' => $session->title
        ]);
    }
}
