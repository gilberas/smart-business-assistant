<?php

namespace App\Events\Conversation;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public ?Conversation $conversation,
    ) {}

    public function broadcastOn(): array
    {
        if (!$this->conversation) {
            return [];
        }

        return [
            new Channel("business.{$this->conversation->business_id}.conversations"),
            new Channel("conversation.{$this->conversation->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }
}
