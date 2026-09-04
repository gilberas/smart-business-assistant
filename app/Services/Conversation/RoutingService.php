<?php

namespace App\Services\Conversation;

use App\Enums\Conversation\ConversationStatus;
use App\Enums\Conversation\RoutingType;
use App\Models\Conversation;
use App\Models\User;
use App\Repositories\Conversation\ConversationRepository;
use App\Repositories\Conversation\RoutingHistoryRepository;

class RoutingService
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly RoutingHistoryRepository $historyRepository,
    ) {}

    public function routeToHuman(Conversation $conversation, ?User $routedBy = null, ?string $reason = null): void
    {
        $previousType = $conversation->assigned_to ? 'user' : 'unassigned';

        $this->historyRepository->create([
            'conversation_id' => $conversation->id,
            'from_type' => $previousType,
            'to_type' => 'human',
            'from_id' => $conversation->assigned_to,
            'to_id' => null,
            'reason' => $reason ?? 'Routed to human',
            'routed_by' => $routedBy?->id,
            'created_at' => now(),
        ]);

        $conversation->status = ConversationStatus::HumanReply;
        $conversation->save();
    }

    public function routeToAi(Conversation $conversation, ?User $routedBy = null, ?string $reason = null): void
    {
        $previousType = $conversation->assigned_to ? 'user' : 'unassigned';

        $this->historyRepository->create([
            'conversation_id' => $conversation->id,
            'from_type' => $previousType,
            'to_type' => 'ai',
            'from_id' => $conversation->assigned_to,
            'to_id' => null,
            'reason' => $reason ?? 'Routed to AI',
            'routed_by' => $routedBy?->id,
            'created_at' => now(),
        ]);

        $conversation->status = ConversationStatus::AiProcessing;
        $conversation->save();
    }

    public function routeToUser(Conversation $conversation, User $targetUser, ?User $routedBy = null, ?string $reason = null): void
    {
        $previousAssignee = $conversation->assigned_to;

        $this->historyRepository->create([
            'conversation_id' => $conversation->id,
            'from_type' => $previousAssignee ? 'user' : 'unassigned',
            'to_type' => 'user',
            'from_id' => $previousAssignee,
            'to_id' => $targetUser->id,
            'reason' => $reason ?? 'Assigned to user',
            'routed_by' => $routedBy?->id,
            'created_at' => now(),
        ]);

        $conversation->assigned_to = $targetUser->id;
        $conversation->status = ConversationStatus::Assigned;
        $conversation->save();
    }

    public function getHistory(int $conversationId): array
    {
        return $this->historyRepository->getForConversation($conversationId)->toArray();
    }

    public function determineRouting(Conversation $conversation): RoutingType
    {
        $businessSettings = $conversation->business->settings;

        if ($businessSettings && $businessSettings->value('default_routing')) {
            return RoutingType::tryFrom($businessSettings->value('default_routing')) ?? RoutingType::Ai;
        }

        if ($conversation->assigned_to) {
            return RoutingType::Human;
        }

        return RoutingType::Ai;
    }
}
