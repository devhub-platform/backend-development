<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Notifications\ReactNotification;
use Tests\TestCase;

class ReactNotificationTest extends TestCase
{
    public function test_it_includes_explicit_sender_payload_in_database_data(): void
    {
        $sender = new User();
        $sender->id = 123;
        $sender->name = 'React User';
        $sender->username = 'react_user';
        $sender->avatar_url = 'https://example.com/avatar.png';

        $post = new Post();
        $post->title = 'Larav';

        $notification = new ReactNotification($post, 'love', $sender);
        $data = $notification->toDatabase(new User());

        $this->assertSame('New reaction on your post: Larav', $data['message']);
        $this->assertSame('love', $data['reaction_type']);
        $this->assertSame([
            'id' => 123,
            'name' => 'React User',
            'username' => 'react_user',
            'avatar_url' => 'https://example.com/avatar.png',
        ], $data['from']);
    }

    public function test_it_sets_from_to_null_when_sender_is_missing(): void
    {
        $post = new Post();
        $post->title = 'Larav';

        $notification = new ReactNotification($post, 'love');
        $data = $notification->toDatabase(new User());

        $this->assertNull($data['from']);
    }
}

