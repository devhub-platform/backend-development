<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use App\Models\AIChatMessage;
use App\Models\AIChatSession;
use App\Models\Attachment;
use App\Services\AzureBlobStorageService;
use App\Services\Chat\ChatHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HistoryController extends Controller
{
    public function __construct(
        protected AzureBlobStorageService $azure,
        protected ChatHistoryService      $history,
    ) {}

    public function sessions(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;

        if (!$userId) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $perPage     = (int) $request->query('per_page', 20);
        $currentPage = (int) $request->query('page', 1);

        // Serve from cache — no DB query on repeated calls
        $all   = $this->history->getSessionsList($userId);
        $total = $all->count();
        $items = $all->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $modelTitles = collect(config('ai_models.chat', []))->keyBy('id');

        return response()->json([
            'sessions' => $items->map(fn($s) => [
                'id'            => $s->id,
                'title'         => $s->title,
                'model'         => $s->model,
                'model_title'   => $modelTitles->get($s->model)['title'] ?? $s->model,
                'message_count' => $s->messages_count,
                'created_at'    => $s->created_at,
                'updated_at'    => $s->updated_at,
                'pinned'        => (bool) $s->pinned,
                'active'        => (bool) $s->active,
            ]),
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $currentPage,
                'last_page'    => (int) ceil($total / max($perPage, 1)),
            ],
        ]);
    }

    public function show(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        // Serve messages from cache
        $messages = $this->history->getSessionMessages($session->id);

        return response()->json([
            'session' => [
                'id'         => $session->id,
                'title'      => $session->title,
                'model'      => $session->model,
                'pinned'     => (bool) $session->pinned,
                'created_at' => $session->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $session->updated_at->format('Y-m-d H:i:s'),
            ],
            'messages' => $messages->map(fn($m) => [
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
            'model' => 'nullable|string',
        ]);

        $userId  = $request->user()?->id;
        $session = AIChatSession::create([
            'user_id' => $userId,
            'title'   => $request->title ?? 'New Chat',
            'model'   => $request->model ?? config('ai_models.default'),
            'active'  => true,
            'pinned'  => false,
        ]);

        $this->history->bustSessionsListCache($userId);

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
        if (!$session) {
            return $this->notFound();
        }

        $userId        = $request->user()->id;
        $messages      = AIChatMessage::where('ai_chat_session_id', $sessionId)->get();
        $attachmentIds = $messages
            ->flatMap(fn($m) => $m->attachments ?? [])
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (!empty($attachmentIds)) {
            $attachments = Attachment::whereIn('id', $attachmentIds)
                ->where('user_id', $userId)
                ->get();

            foreach ($attachments as $attachment) {
                $path = $attachment->blob_path ?? $attachment->s3_path ?? null;
                if ($path) {
                    $this->azure->delete($path);
                }
            }

            Attachment::whereIn('id', $attachmentIds)
                ->where('user_id', $userId)
                ->delete();
        }

        $messages->each->delete();
        $session->delete();

        // Bust all related caches
        $this->history->bustMessagesCache((int) $sessionId);
        $this->history->bustSessionCache((int) $sessionId, $userId);
        $this->history->bustSessionsListCache($userId);
        Cache::forget("chat:ctx:{$sessionId}");

        return response()->json(['message' => 'Session deleted successfully']);
    }

    public function pin(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        $this->history->pinSession($session->id);

        return response()->json(['message' => 'Session pinned', 'pinned' => true]);
    }

    public function unpin(Request $request, $sessionId): JsonResponse
    {
        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        $this->history->unpinSession($session->id);

        return response()->json(['message' => 'Session unpinned', 'pinned' => false]);
    }

    public function updateTitle(Request $request, $sessionId): JsonResponse
    {
        $request->validate(['title' => 'required|string|max:255']);

        $session = $this->findUserSession($sessionId, $request->user()?->id);
        if (!$session) {
            return $this->notFound();
        }

        $this->history->updateSessionTitle($session->id, $request->title);

        return response()->json(['message' => 'Title updated', 'title' => $request->title]);
    }

    // -------------------------------------------------------------------------

    private function findUserSession($sessionId, ?int $userId): ?AIChatSession
    {
        if (!$userId) {
            return null;
        }

        // Try cache first, fall back to DB
        $cached = $this->history->getCachedSession((int) $sessionId, $userId);
        return $cached ?? AIChatSession::where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['error' => 'Session not found'], 404);
    }
}
