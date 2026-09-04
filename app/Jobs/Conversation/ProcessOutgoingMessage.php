<?php

namespace App\Jobs\Conversation;

use App\Models\Message;
use App\Services\Conversation\ConversationEventService;
use App\Services\Conversation\MessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOutgoingMessage implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public int $conversationId,
        public array $messageData,
    ) {}

    public function handle(
        MessageService $messageService,
        ConversationEventService $eventService,
    ): void {
        $message = $messageService->send($this->conversationId, $this->messageData);

        $eventService->addEvent(
            $this->conversationId,
            'message_sent',
            ['message_id' => $message->id, 'content_type' => $this->messageData['content_type'] ?? 'text'],
        );
    }
}
