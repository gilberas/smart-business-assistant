<?php

namespace App\Enums\AI;

enum PromptType: string
{
    case System = 'system';
    case Business = 'business';
    case Conversation = 'conversation';
    case Knowledge = 'knowledge';
    case Classification = 'classification';
    case Moderation = 'moderation';
    case Summarization = 'summarization';

    public function label(): string
    {
        return match ($this) {
            self::System => 'System Prompt',
            self::Business => 'Business Prompt',
            self::Conversation => 'Conversation Prompt',
            self::Knowledge => 'Knowledge Prompt',
            self::Classification => 'Classification Prompt',
            self::Moderation => 'Moderation Prompt',
            self::Summarization => 'Summarization Prompt',
        };
    }
}
