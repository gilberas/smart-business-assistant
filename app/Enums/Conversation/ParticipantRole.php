<?php

namespace App\Enums\Conversation;

enum ParticipantRole: string
{
    case Owner = 'owner';
    case Agent = 'agent';
    case Observer = 'observer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Agent => 'Agent',
            self::Observer => 'Observer',
        };
    }
}
