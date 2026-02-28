<?php

namespace App\Http\Controllers\V1\Chats;

use App\Http\Controllers\V1\Controller;
use App\Http\Requests\SendMessageAttchmentRequest;
use App\Http\Requests\SendMessageRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Musonza\Chat\Facades\ChatFacade as Chat;
use Musonza\Chat\Models\Conversation;
use App\Policies\ChatPolicy;

use App\Services\AWSS3Service;
use App\Services\ImageUploadCloudinaryService;

class MessageController extends Controller
{
    public function __construct(
        private ImageUploadCloudinaryService $cloudinaryService,
        private AWSS3Service $awsS3Service
    ) {
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
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

        return response()->json([
            'message' => 'Message sent.',
            'data' => $message,
        ], 201);
    }

    public function sendMessageWithAttachment(SendMessageAttchmentRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validated();
        $file = $request->file('file');
        $fileName = $validated['file_name'] ?? $file->getClientOriginalName();

        $fileUrl = $this->awsS3Service->uploadFile($file, 'chat_attachments');

        $message = Chat::message('Attachment')
            ->type('attachment')
            ->data([
                'file_name' => $fileName,
                'file_url'  => $fileUrl,
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

        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant(auth()->user())
            ->delete();

        return response()->json(['message' => 'Message deleted.']);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        Chat::conversation($conversation)
            ->setParticipant(auth()->user())
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

    public function toggleReaction(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'reaction' => 'required|string|max:255',
        ]);

        $message = Chat::messages()->getById($messageId);
        $result = Chat::message($message)
            ->setParticipant($request->user())
            ->toggleReaction($validated['reaction']);

        return response()->json([
            'message' => $result['added'] ? 'Reaction added.' : 'Reaction removed.',
            'data' => $result
        ]);
    }

    public function reactToMessage(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'reaction' => 'required|string|max:255',
        ]);

        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant($request->user())
            ->react($validated['reaction']);

        return response()->json(['message' => 'Reaction added to message.']);
    }

    public function unreactToMessage(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        $validated = $request->validate([
            'reaction' => 'required|string|max:255',
        ]);

        $message = Chat::messages()->getById($messageId);
        Chat::message($message)
            ->setParticipant($request->user())
            ->unreact($validated['reaction']);

        return response()->json(['message' => 'Reaction removed from message.']);
    }

    public function getReactionsSummary(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        $message = Chat::messages()->getById($messageId);
        $summary = Chat::message($message)->reactionsSummary();

        return response()->json([
            'data' => $summary
        ]);
    }

    public function makeMessageAsFlagged(Request $request, Conversation $conversation, int $messageId): JsonResponse
    {
        $this->authorize('view', $conversation);

        Chat::messages()
            ->setParticipant($request->user())->toggleFlag();

        return response()->json(['message' => 'Message flagged.']);
    }

    public function broadcastTest(Request $request): JsonResponse
    {
        event(new \App\Events\MyEvent('Menna Sayed sent a message'));
        return response()->json(['message' => 'Broadcast event sent.']);
    }
}
