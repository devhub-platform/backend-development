<?php

namespace App\Services\Notifications;

use App\Models\Answer;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class NotificationFormatterService
{
    private const TYPE_NEW_COMMENT = 'App\\Notifications\\NewCommentNotification';
    private const TYPE_REACT = 'App\\Notifications\\ReactNotification';
    private const TYPE_FOLLOW = 'App\\Notifications\\FollowNotification';
    private const TYPE_NEW_POST = 'App\\Notifications\\NewPostNotification';
    private const TYPE_MENTION = 'App\\Notifications\\MentionInCommentNotification';
    private const TYPE_QUESTION_CREATED = 'App\\Notifications\\QuestionCreatedNotification';
    private const TYPE_NEW_ANSWER = 'App\\Notifications\\NewAnswerNotification';
    private const TYPE_ANSWER_ACCEPTED = 'App\\Notifications\\AnswerAcceptedNotification';

    public function formatMany(iterable $notifications): Collection
    {
        return collect($notifications)
            ->map(fn ($notification) => $this->format($notification))
            ->values();
    }

    public function format($notification): array
    {
        return match ($notification->type ?? null) {
            self::TYPE_NEW_COMMENT => $this->formatNewComment($notification),
            self::TYPE_REACT => $this->formatReaction($notification),
            self::TYPE_FOLLOW => $this->formatFollow($notification),
            self::TYPE_NEW_POST => $this->formatNewPost($notification),
            self::TYPE_MENTION => $this->formatMention($notification),
            self::TYPE_QUESTION_CREATED => $this->formatQuestion($notification),
            self::TYPE_NEW_ANSWER => $this->formatNewAnswer($notification),
            self::TYPE_ANSWER_ACCEPTED => $this->formatAnswerAccepted($notification),
            default => $this->formatGeneric($notification),
        };
    }

    private function formatGeneric($notification): array
    {
        $data = $this->notificationData($notification);

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'data' => $data,
        ]);
    }

    private function formatNewComment($notification): array
    {
        $data = $this->notificationData($notification);
        $commenter = $this->normalizeUser($data['commenter_user'] ?? []);
        $commentId = $data['comment_id'] ?? null;
        $postId = $data['post_id'] ?? null;
        $postTitle = $data['post_title'] ?? null;
        $postSlug = $data['post_slug'] ?? null;
        $commentBody = $data['comment_body'] ?? null;

        if ($postId === null || $postTitle === null || $postSlug === null) {
            $legacyTitle = str_replace('New comment on your post: ', '', (string) ($data['message'] ?? ''));
            $post = null;

            if ($legacyTitle !== '') {
                $post = Post::query()
                    ->where('title', $legacyTitle)
                    ->orWhere('title', 'like', '%' . $legacyTitle . '%')
                    ->first();
            }

            $postId = $postId ?? $post?->id;
            $postTitle = $postTitle ?? $post?->title;
            $postSlug = $postSlug ?? $post?->slug;
        }

        if ($commentId === null && ($postId !== null || $postTitle !== null) && $commentBody !== null) {
            $commentQuery = Comment::query()->where('content', $commentBody);

            if ($postId !== null) {
                $commentQuery->where('post_id', $postId);
            }

            if (data_get($commenter, 'id')) {
                $commentQuery->where('user_id', data_get($commenter, 'id'));
            }

            $commentId = $commentQuery->latest('id')->value('id');
        }

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'post' => [
                'id' => $postId,
                'title' => $postTitle,
                'slug' => $postSlug,
            ],
            'comment' => [
                'id' => $commentId,
                'body' => $commentBody,
            ],
            'commenter' => $commenter,
        ]);
    }

    private function formatReaction($notification): array
    {
        $data = $this->notificationData($notification);
        $from = $this->normalizeUser($data['from'] ?? []);

        if ($from === [] && data_get($data, 'from.id')) {
            $from = $this->normalizeUser(User::query()->find(data_get($data, 'from.id')));
        }

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'reaction_type' => $data['reaction_type'] ?? null,
            'from' => $from,
        ]);
    }

    private function formatFollow($notification): array
    {
        $data = $this->notificationData($notification);
        $follower = $this->normalizeUser([
            'id' => $data['follower_id'] ?? null,
            'name' => $data['follower_name'] ?? null,
            'username' => $data['follower_username'] ?? null,
            'avatar_url' => $data['follower_avatar'] ?? null,
        ]);

        if (($follower['id'] ?? null) === null && ($data['follower_id'] ?? null) !== null) {
            $follower = $this->normalizeUser(User::query()->find($data['follower_id']));
        }

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'follower' => $follower,
            'total_followers' => $data['total_followers'] ?? null,
        ]);
    }

    private function formatNewPost($notification): array
    {
        $data = $this->notificationData($notification);
        $author = $this->normalizeUser([
            'id' => $data['author_id'] ?? null,
            'name' => $data['author_name'] ?? null,
            'username' => $data['author_username'] ?? null,
            'avatar_url' => $data['author_avatar'] ?? null,
        ]);
        $post = [
            'id' => $data['post_id'] ?? null,
            'title' => $data['post_title'] ?? null,
            'slug' => $data['post_slug'] ?? null,
        ];

        if (($author['id'] ?? null) === null && ($post['id'] ?? null) !== null) {
            $postModel = Post::query()->with('user')->find($post['id']);

            if ($postModel) {
                $post['title'] = $post['title'] ?? $postModel->title;
                $post['slug'] = $post['slug'] ?? $postModel->slug;
                $author = $this->normalizeUser($postModel->user);
            }
        }

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'author' => $author,
            'post' => $post,
        ]);
    }

    private function formatQuestion($notification): array
    {
        $data = $this->notificationData($notification);
        $asker = [
            'id' => $data['asker_id'] ?? null,
            'name' => $data['asker_name'] ?? null,
            'username' => $data['asker_username'] ?? null,
            'avatar_url' => $data['asker_avatar'] ?? null,
        ];

        if (($asker['id'] ?? null) === null || ($asker['username'] ?? null) === null || ($asker['avatar_url'] ?? null) === null) {
            $askerModel = User::query()
                ->when(($asker['id'] ?? null) !== null, fn ($query) => $query->whereKey($asker['id']))
                ->when(($asker['username'] ?? null) !== null, fn ($query) => $query->orWhere('username', $asker['username']))
                ->when(($asker['username'] ?? null) === null && ($asker['name'] ?? null) !== null, fn ($query) => $query->orWhere('name', $asker['name']))
                ->first();

            $asker = array_merge($asker, $this->normalizeUser($askerModel));
        }

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'asker' => $asker,
            'question' => [
                'id' => $data['question_id'] ?? null,
                'title' => $data['question_title'] ?? null,
            ],
            'post' => [
                'id' => $data['post_id'] ?? null,
            ],
        ]);
    }

    private function formatMention($notification): array
    {
        $data = $this->notificationData($notification);
        $mentionedBy = [
            'id' => $data['mentioned_by_id'] ?? null,
            'name' => $data['mentioned_by_name'] ?? null,
            'username' => $data['mentioned_by_username'] ?? null,
            'avatar_url' => $data['mentioned_by_avatar'] ?? null,
        ];

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'post' => [
                'id' => $data['post_id'] ?? null,
                'title' => $data['post_title'] ?? null,
            ],
            'comment' => [
                'id' => $data['comment_id'] ?? null,
                'body' => $data['comment_content'] ?? null,
            ],
            'mentioned_by' => $mentionedBy,
        ]);
    }

    private function formatNewAnswer($notification): array
    {
        $data = $this->notificationData($notification);
        $answerer = $this->normalizeUser($data['answerer_from_user'] ?? []);
        $question = [
            'id' => $data['question_id'] ?? null,
            'title' => $data['question_title'] ?? null,
        ];
        $answer = [
            'id' => $data['answer_id'] ?? null,
        ];

        if (($answer['id'] ?? null) !== null && ($answerer['id'] ?? null) === null) {
            $answerModel = Answer::query()->with(['user', 'question'])->find($answer['id']);

            if ($answerModel) {
                $answerer = $this->normalizeUser($answerModel->user);
                $question['id'] = $question['id'] ?? $answerModel->question_id;
                $question['title'] = $question['title'] ?? $answerModel->question?->title;
            }
        }

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'question' => $question,
            'answer' => $answer,
            'answerer' => $answerer,
        ]);
    }

    private function formatAnswerAccepted($notification): array
    {
        $data = $this->notificationData($notification);
        $question = [
            'id' => $data['question_id'] ?? null,
            'title' => $data['question_title'] ?? null,
        ];
        $answer = [
            'id' => $data['answer_id'] ?? null,
        ];

        if (($answer['id'] ?? null) !== null && ($question['title'] ?? null) === null) {
            $answerModel = Answer::query()->with('question')->find($answer['id']);

            if ($answerModel) {
                $question['id'] = $question['id'] ?? $answerModel->question_id;
                $question['title'] = $question['title'] ?? $answerModel->question?->title;
            }
        }

        return array_merge($this->base($notification), [
            'message' => $data['message'] ?? null,
            'question' => $question,
            'answer' => $answer,
        ]);
    }

    private function base($notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'created_at' => $notification->created_at,
            'read_at' => $notification->read_at,
        ];
    }

    private function notificationData($notification): array
    {
        $data = $notification->data ?? [];

        return is_array($data) ? $data : (array) $data;
    }

    private function formatTimestamp($timestamp): ?string
    {
        return $timestamp ? $timestamp->format('M d, Y \a\t h:i A') : null;
    }

    private function normalizeUser($user): array
    {
        if ($user instanceof User) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
            ];
        }

        if ($user instanceof EloquentCollection) {
            return [];
        }

        if (is_object($user)) {
            return [
                'id' => $user->id ?? null,
                'name' => $user->name ?? null,
                'username' => $user->username ?? null,
                'avatar_url' => $user->avatar_url ?? null,
            ];
        }

        if (is_array($user)) {
            return [
                'id' => $user['id'] ?? null,
                'name' => $user['name'] ?? null,
                'username' => $user['username'] ?? null,
                'avatar_url' => $user['avatar_url'] ?? null,
            ];
        }

        return [];
    }
}
