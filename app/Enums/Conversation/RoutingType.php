<?php

namespace App\Enums\Conversation;

enum RoutingType: string
{
    case Human = 'human';
    case Ai = 'ai';
    case Department = 'department';
    case Queue = 'queue';
    case RoundRobin = 'round_robin';
    case Priority = 'priority';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Human => 'Human Agent',
            self::Ai => 'AI Assistant',
            self::Department => 'Department',
            self::Queue => 'Queue',
            self::RoundRobin => 'Round Robin',
            self::Priority => 'Priority',
            self::Custom => 'Custom',
        };
    }
}
