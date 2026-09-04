<?php

namespace App\Jobs\Conversation;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\ConversationEventService;
use App\Services\Conversation\MessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public int $conversationId,
        public array $messageData,
    ) {}

    public function handle(
        MessageService $messageService,
        ConversationService $conversationService,
        ConversationEventService $eventService,
    ): void {
        $conversation = $conversationService->find($this->conversationId);
        if (!$conversation) {
            return;
        }

        $message = $messageService->receive($this->conversationId, $this->messageData, $conversation);

        $eventService->addEvent(
            $this->conversationId,
            'message_received',
            ['message_id' => $message->id, 'sender_type' => $this->messageData['sender_type'] ?? 'customer'],
        );

        if ($conversation->status->isActive() === false) {
            $conversationService->reopen($conversation);
        }
    }
}
