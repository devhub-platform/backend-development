<?php

namespace App\Http\Controllers\V1\Chats;

use App\Events\MessageDeleted;
use App\Events\MessageReactionUpdated;
use App\Events\MessageUpdated;
use App\Http\Controllers\V1\Controller;
use App\Http\Resources\MessageResource;
use App\Http\Requests\MessagesRequests\SendMessageAttchmentRequest;
use App\Http\Requests\MessagesRequests\SendMessageRequest;
use App\Http\Requests\MessagesRequests\SendVoiceMessageRequest;
use App\Services\AWSS3Service;
use App\Services\HackClubCdnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use OneSignal;

class MessageController extends Controller
{
    public function __construct(
        private AWSS3Service $awsS3Service,
        private HackClubCdnService $hackClubCdnService
    )
    {
    }

    public function sendMessage(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $validated = $request->validated();

        $message = Chat::message($validated['message'])
            ->type($validated['type'] ?? 'text');

        if (isset($validated['data'])) {
            $message->data($validated['data']);
        }

        $message = $message->from(auth()->user())
            ->to($conversation)
            ->send();

        OneSignal::sendNotificationToUser(
            $validated['message'],
            $conversation->participants()->where('user_id', '!=', auth()->id())->first()->user->onesignal_player_id,
            'deeplink://chats?id=' . $conversation->id,
            null,
            null,
            'New message from ' . (auth()->user()->name)
        );


        return response()->json([
            'message' => 'Message sent.',
            'data' => new MessageResource($message),
        ], 201);
    }

    public function sendMessageWithAttachment(SendMessageAttchmentRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $validated = $request->validated();
        $file = $request->file('file');
        $fileName = $validated['file_name'] ?? $file->getClientOriginalName();

        $fileUrl = $this->hackClubCdnService->uploadFileUrl($file);

        $attachmentMessage = Chat::message('Attachment')
            ->type('attachment')
            ->data([
                'file_name' => $fileName,
                'file_url' => $fileUrl,
            ])
            ->from(auth()->user())
            ->to($conversation)
            ->send();

         OneSignal::sendNotificationToUser(
             $validated['message'],
             $conversation->participants()->where('user_id', '!=', auth()->id())->first()->user->onesignal_player_id,
             'deeplink://chats?id=' . $conversation->id,
             null,
             null,
             'New attachment from ' . (auth()->user()->name)
         );

        $messages = [
            'attachment' => new MessageResource($attachmentMessage)
        ];

        if (!empty($validated['message'])) {
            $textMessage = Chat::message($validated['message'])
                ->type('text')
                ->from(auth()->user())
                ->to($conversation)
                ->send();

            $messages['text'] = new MessageResource($textMessage);
        }

        return response()->json([
            'message' => 'Attachment sent successfully.' . (!empty($validated['message']) ? ' Text message also sent.' : ''),
            'data' => $messages,
        ], 201);
    }

    public function sendVoiceMessage(SendVoiceMessageRequest $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $validated = $request->validated();
        $file = $request->file('file');
        $fileName = $validated['file_name'] ?? $file->getClientOriginalName();
        $durationMs = $validated['duration_ms'] ?? null;

        $fileUrl = $this->hackClubCdnService->uploadFileUrl($file);

        $voiceMessage = Chat::message($validated['message'] ?? 'Voice message')
            ->type('voice')
            ->data([
                'file_name' => $fileName,
                'file_url' => $fileUrl,
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'duration_ms' => $durationMs,
            ])
            ->from(auth()->user())
            ->to($conversation)
            ->send();

        OneSignal::sendNotificationToUser(
            $validated['message'],
            $conversation->participants()->where('user_id', '!=', auth()->id())->first()->user->onesignal_player_id,
            'deeplink://chats?id=' . $conversation->id,
            null,
            null,
            'New voice message from ' . (auth()->user()->name)
        );

        return response()->json([
            'message' => 'Voice message sent successfully.',
            'data' => new MessageResource($voiceMessage),
        ], 201);
    }

    public function deleteMessage(int $messageId, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant(auth()->user())
            ->delete();

        event(new MessageDeleted($messageId, $conversation->id));

        return response()->json([
            'message' => 'Message deleted successfully.',
            'data' => [
                'id' => $messageId,
                'conversation_id' => $conversation->id,
                'deleted_at' => now()->format('Y-m-d H:i:s'),
            ]
        ], 200);
    }

    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        Chat::conversation($conversation)
            ->setParticipant(auth()->user())
            ->readAll();

        return response()->json([
            'message' => 'All messages marked as read.',
            'data' => [
                'conversation_id' => $conversation->id,
                'marked_at' => now()->format('Y-m-d H:i:s'),
            ]
        ], 200);
    }

    public function updateMessage(Request $request, int $messageId, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'type' => 'nullable|string|in:text,image,video,file',
        ]);

        $message = Chat::messages()->getById($messageId);

        $message->update([
            'body' => $validated['message'],
            'type' => $validated['type'] ?? 'text',
        ]);

        $updatedMessage = $message->fresh();

        broadcast(new MessageUpdated($updatedMessage, $conversation->id))->toOthers();

        return response()->json([
            'message' => 'Message updated successfully.',
            'data' => new MessageResource($updatedMessage),
        ], 200);
    }

    public function toggleReaction(Request $request, int $messageId, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'reaction' => 'required|string|max:255',
        ]);

        $message = Chat::messages()->getById($messageId);
        $result = Chat::message($message)
            ->setParticipant($request->user())
            ->toggleReaction($validated['reaction']);

        $reactions = $this->normalizeReactionsSummary(Chat::message($message)->reactionsSummary());

        broadcast(new MessageReactionUpdated(
            messageId: $messageId,
            conversationId: $conversation->id,
            userId: (int)$request->user()->id,
            reaction: $validated['reaction'],
            action: $result['added'] ? 'added' : 'removed',
            reactions: $reactions,
        ))->toOthers();

        return response()->json([
            'message' => $result['added'] ? 'Reaction added.' : 'Reaction removed.',
            'data' => [
                'message_id' => $messageId,
                'conversation_id' => $conversation->id,
                'reaction' => $validated['reaction'],
                'added' => $result['added'],
                'toggled_at' => now()->toIso8601String(),
                'details' => $result,
                'reactions' => $reactions,
            ]
        ], 200);
    }

    public function reactToMessage(Request $request, int $messageId, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'reaction' => 'required|string|max:255',
        ]);

        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant($request->user())
            ->react($validated['reaction']);

        $reactions = $this->normalizeReactionsSummary(Chat::message($message)->reactionsSummary());

        OneSignal::sendNotificationToAll(
            $validated['reaction'],
            'https://dev-hubs.tech',
            null,
            null,
            null,
            'New reaction from ' . (auth()->user()->name ?? auth()->user()->username)
        );

        broadcast(new MessageReactionUpdated(
            messageId: $messageId,
            conversationId: $conversation->id,
            userId: (int)$request->user()->id,
            reaction: $validated['reaction'],
            action: 'added',
            reactions: $reactions,
        ))->toOthers();

        return response()->json([
            'message' => 'Reaction added to message.',
            'data' => [
                'message_id' => $messageId,
                'conversation_id' => $conversation->id,
                'reaction' => $validated['reaction'],
                'reacted_at' => now()->format('Y-m-d H:i:s'),
                'reactions' => $reactions,
            ]
        ], 201);
    }

    public function addReactionToMessage(Request $request, int $messageId, int $conversationId): JsonResponse
    {
        return $this->reactToMessage($request, $messageId, $conversationId);
    }

    public function unreactToMessage(Request $request, int $messageId, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'reaction' => 'required|string|max:255',
        ]);

        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant($request->user())
            ->unreact($validated['reaction']);

        $reactions = $this->normalizeReactionsSummary(Chat::message($message)->reactionsSummary());

        broadcast(new MessageReactionUpdated(
            messageId: $messageId,
            conversationId: $conversation->id,
            userId: (int)$request->user()->id,
            reaction: $validated['reaction'],
            action: 'removed',
            reactions: $reactions,
        ))->toOthers();

        return response()->json([
            'message' => 'Reaction removed from message.',
            'data' => [
                'message_id' => $messageId,
                'conversation_id' => $conversation->id,
                'reaction' => $validated['reaction'],
                'unreacted_at' => now()->format('Y-m-d H:i:s'),
                'reactions' => $reactions,
            ]
        ], 200);
    }

    public function getReactionsSummary(Request $request, int $messageId, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $message = Chat::messages()->getById($messageId);
        $summary = $this->normalizeReactionsSummary(Chat::message($message)->reactionsSummary());

        return response()->json([
            'message' => 'Reactions summary retrieved successfully.',
            'data' => [
                'message_id' => $messageId,
                'conversation_id' => $conversation->id,
                'reactions' => $summary
            ]
        ], 200);
    }

    public function makeMessageAsFlagged(Request $request, int $messageId, int $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $this->authorize('view', $conversation);

        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant($request->user())
            ->toggleFlag();

        return response()->json([
            'message' => 'Message flagged successfully.',
            'data' => [
                'message_id' => $messageId,
                'conversation_id' => $conversation->id,
                'flagged_at' => now()->toIso8601String(),
            ]
        ], 200);
    }

    public function broadcastTest(Request $request): JsonResponse
    {
        event(new \App\Events\MyEvent('Menna sent a message'));
        return response()->json(['message' => 'Broadcast event sent.']);
    }

    private function normalizeReactionsSummary(mixed $summary): array
    {
        if (is_array($summary)) {
            return $summary;
        }

        if ($summary instanceof Collection) {
            return $summary->toArray();
        }

        return (array)$summary;
    }
}
