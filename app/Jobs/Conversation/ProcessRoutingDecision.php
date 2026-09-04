<?php

namespace App\Jobs\Conversation;

use App\Services\Conversation\ConversationService;
use App\Services\Conversation\ConversationEventService;
use App\Services\Conversation\RoutingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessRoutingDecision implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public int $conversationId,
    ) {}

    public function handle(
        ConversationService $conversationService,
        RoutingService $routingService,
        ConversationEventService $eventService,
    ): void {
        $conversation = $conversationService->find($this->conversationId);
        if (!$conversation) {
            return;
        }

        $routingType = $routingService->determineRouting($conversation);

        match ((string) $routingType) {
            'human' => $routingService->routeToHuman($conversation),
            'ai' => $routingService->routeToAi($conversation),
            default => $routingService->routeToHuman($conversation),
        };

        $eventService->addEvent(
            $this->conversationId,
            'routed',
            ['to' => (string) $routingType],
        );
    }
}
