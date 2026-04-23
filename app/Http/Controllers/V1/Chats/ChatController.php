<?php

namespace App\Http\Controllers\V1\Chats;

use App\Events\MessageDeleted;
use App\Http\Controllers\V1\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\User;
use App\Services\OneSignalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;

class ChatController extends Controller
{
    public function __construct(
        private OneSignalService $oneSignalService
    ) {}
    public function index(Request $request): JsonResponse
    {
        $conversations = Chat::conversations()
            ->setParticipant($request->user())
            ->isDirect()
            ->setPaginationParams(['perPage' => $request->integer('per_page', 15)])
            ->get();

        return response()->json($conversations);
    }

    public function createOrGetConversation(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', 'different:' . $request->user()->id],
        ]);

        $authUser = $request->user();
        $otherUser = User::findOrFail($request->integer('user_id'));

        $existing = Chat::conversations()->between($authUser, $otherUser);

        if ($existing) {
            return response()->json([
                'message' => 'Conversation already exists.',
                'conversation' => $existing,
            ]);
        }

        $conversation = Chat::makeDirect()->createConversation([$authUser, $otherUser]);

        return response()->json([
            'message' => 'Conversation created successfully.',
            'conversation' => $conversation,
        ], 201);
    }


    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return response()->json($conversation->load('participants'));
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        Chat::conversation($conversation)
            ->setParticipant($request->user())
            ->clear();

        return response()->json(['message' => 'Conversation cleared successfully.']);
    }

    public function getMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = Chat::conversation($conversation)
            ->setParticipant($request->user())
            ->setPaginationParams([
                'page' => $request->integer('page', 1),
                'perPage' => $request->integer('per_page', 20)
            ])
            ->getMessages();

        Chat::conversation($conversation)
            ->setParticipant($request->user())
            ->readAll();

        return response()->json([
            'messages' => MessageResource::collection($messages)->response()->getData(true),
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'in:text,image,video,file,attachment'],
            'data' => ['nullable', 'array'],
        ]);

        $message = Chat::message($validated['message'])
            ->type($validated['type'] ?? 'text');

        if (isset($validated['data'])) {
            $message->data($validated['data']);
        }

        $message = $message->from($request->user())
            ->to($conversation)
            ->send();

        // Send OneSignal notification to recipients
        $this->sendMessageNotification($conversation, $request->user(), $validated['message']);

        return response()->json([
            'message' => 'Message sent.',
            'data' => $message,
        ], 201);
    }

    public function deleteMessage(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant($request->user())
            ->delete();

        event(new MessageDeleted($messageId, $conversation->id));

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

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Chat::messages()
            ->setParticipant($request->user())
            ->unreadCount();

        return response()->json(['unread_count' => $count]);
    }

    public function clearConversations(Conversation $conversation, Request $request)
    {
        $this->authorize('view', $conversation);

        Chat::conversation($conversation)
            ->setParticipant($request->user())
            ->clear();

        return response()->json(['message' => 'Conversation cleared successfully.']);
    }

    /**
     * Send OneSignal push notification to conversation recipients
     *
     * @param Conversation $conversation
     * @param User $sender
     * @param string $messagePreview
     * @param string|null $title
     * @return void
     */
    private function sendMessageNotification(Conversation $conversation, User $sender, string $messagePreview, ?string $title = null): void
    {
        try {
            // Get all participants except the sender
            $participants = $conversation->getParticipants();
            $recipientPlayerIds = [];

            foreach ($participants as $participant) {
                // Skip the sender and users without OneSignal player ID
                if ($participant->id !== $sender->id && $participant->onesignal_player_id) {
                    // Check notification preferences
                    $notificationPreferences = $participant->notification_preferences ?? [];

                    // Send if preferences are not set or if chat notifications are enabled
                    if (empty($notificationPreferences) || ($notificationPreferences['messages'] ?? true)) {
                        $recipientPlayerIds[] = $participant->onesignal_player_id;
                    }
                }
            }

            // Send notification if there are recipients
            if (!empty($recipientPlayerIds)) {
                $heading = $title ?? "{$sender->name} sent a message";
                $truncatedMessage = strlen($messagePreview) > 100
                    ? substr($messagePreview, 0, 97) . '...'
                    : $messagePreview;

                $this->oneSignalService->sendToUsers(
                    message: $truncatedMessage,
                    playerIds: $recipientPlayerIds,
                    heading: $heading,
                    data: [
                        'conversation_id' => $conversation->id,
                        'sender_id' => $sender->id,
                        'sender_name' => $sender->name,
                        'type' => 'chat_message',
                    ]
                );
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the message sending
            \Illuminate\Support\Facades\Log::error('Failed to send OneSignal notification', [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
