<?php

namespace App\Http\Controllers\V1\Chats;

use App\Http\Controllers\V1\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use App\Policies\ChatPolicy;

class MessageController extends Controller
{
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'type' => 'nullable|string|in:text,image,video,file',
        ]);

        $message = Chat::message($validated['message'])
            ->type($validated['type'] ?? 'text')
            ->from(auth()->user())
            ->to($conversation)
            ->send();

        return response()->json([
            'message' => 'Message sent.',
            'data' => $message,
        ], 201);
    }

    public function sendMessageWithAttachment(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'file_name' => 'required|string|max:255',
            'file_url'  => 'required|url',
        ]);

        $message = Chat::message('Attachment')
            ->type('attachment')
            ->data([
                'file_name' => $validated['file_name'],
                'file_url'  => $validated['file_url'],
            ])
            ->from(auth()->user())
            ->to($conversation)
            ->send();

        return response()->json([
            'message' => 'Attachment sent.',
            'data'    => $message,
        ], 201);
    }

    public function deleteMessage(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        Chat::messages()
            ->setParticipant($request->user())
            ->setMessage(Chat::messages()->getById($messageId))
            ->delete();

        return response()->json(['message' => 'Message deleted.']);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        Chat::conversation($conversation)
            ->setParticipant($request->user())
            ->readAll();

        return response()->json(['message' => 'All messages marked as read.']);
    }

    public function updateMessage(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'type' => 'nullable|string|in:text,image,video,file',
        ]);

        Chat::messages()
            ->setParticipant($request->user())
            ->setMessage(Chat::messages()->getById($messageId))
            ->update([
                'body' => $validated['message'],
                'type' => $validated['type'] ?? 'text',
            ]);

        return response()->json(['message' => 'Message updated.']);
    }

    public function addReactionToMessage(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'reaction' => 'required|string|max:255',
        ]);

        Chat::messages()
            ->setParticipant($request->user())
            ->setMessage(Chat::messages()->getById($messageId))
            ->toggleReaction($validated['reaction']);

        return response()->json(['message' => 'Reaction added to message.']);
    }

    public function makeMessageAsFlagged(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        Chat::messages()
            ->setParticipant($request->user())->toggleFlag();

        return response()->json(['message' => 'Message flagged.']);
    }

}
