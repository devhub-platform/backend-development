<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Report $report
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'report.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->report->id,
            'title' => $this->report->title ?? 'New Report',
            'description' => $this->report->description,
            'user_id' => $this->report->user_id,
            'user_name' => $this->report->user?->name ?? 'Anonymous',
            'type' => $this->report->type ?? 'general',
            'status' => $this->report->status ?? 'pending',
            'created_at' => $this->report->created_at,
        ];
    }
}
