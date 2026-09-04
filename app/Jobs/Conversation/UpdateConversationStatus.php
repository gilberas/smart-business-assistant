<?php

namespace App\Jobs\Conversation;

use App\Services\Conversation\ConversationService;
use App\Services\Conversation\ConversationEventService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class UpdateConversationStatus implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public int $conversationId,
        public string $newStatus,
        public ?string $reason = null,
    ) {}

    public function handle(
        ConversationService $conversationService,
        ConversationEventService $eventService,
    ): void {
        $conversation = $conversationService->find($this->conversationId);
        if (!$conversation) {
            return;
        }

        $oldStatus = (string) $conversation->status;
        $conversation->status = $this->newStatus;
        $conversation->save();

        $eventService->addEvent(
            $this->conversationId,
            'status_changed',
            ['from' => $oldStatus, 'to' => $this->newStatus, 'reason' => $this->reason],
        );
    }
}
