<?php

namespace App\Enums\Conversation;

enum ConversationStatus: string
{
    case New = 'new';
    case Waiting = 'waiting';
    case Assigned = 'assigned';
    case AiProcessing = 'ai_processing';
    case HumanReply = 'human_reply';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Waiting => 'Waiting',
            self::Assigned => 'Assigned',
            self::AiProcessing => 'AI Processing',
            self::HumanReply => 'Human Reply',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Archived => 'Archived',
        };
    }

    public function isActive(): bool
    {
        return match ($this) {
            self::New, self::Waiting, self::Assigned, self::AiProcessing, self::HumanReply => true,
            self::Resolved, self::Closed, self::Archived => false,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::New => in_array($target, [self::Waiting, self::Assigned, self::AiProcessing, self::Closed]),
            self::Waiting => in_array($target, [self::Assigned, self::AiProcessing, self::HumanReply, self::Closed]),
            self::Assigned => in_array($target, [self::AiProcessing, self::HumanReply, self::Resolved, self::Closed]),
            self::AiProcessing => in_array($target, [self::Assigned, self::HumanReply, self::Resolved]),
            self::HumanReply => in_array($target, [self::AiProcessing, self::Resolved, self::Closed]),
            self::Resolved => in_array($target, [self::New, self::Assigned, self::Closed, self::Archived]),
            self::Closed => in_array($target, [self::New, self::Archived]),
            self::Archived => in_array($target, [self::Closed]),
        };
    }
}
