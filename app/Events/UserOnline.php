<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserOnline implements ShouldBroadcastNow
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public function __construct(public User $user)
	{
	}

	public function broadcastOn(): Channel
	{
		return new Channel('chat.user-status');
	}

	public function broadcastAs(): string
	{
		return 'user.online';
	}

	public function broadcastWith(): array
	{
		return [
			'id' => $this->user->id,
			'status' => 'online',
			'last_seen_at' => $this->user->lastSeenAtIso(),
		];
	}
}

