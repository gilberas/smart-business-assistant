<?php

namespace App\Events\Conversation;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public User $assignee,
        public ?int $previousAssigneeId,
        public ?string $reason,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("business.{$this->conversation->business_id}.conversations"),
            new Channel("user.{$this->assignee->id}.conversations"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.assigned';
    }
}
