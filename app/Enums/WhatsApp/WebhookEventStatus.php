<?php

namespace App\Enums\WhatsApp;

enum WebhookEventStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case DeadLetter = 'dead_letter';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Processing => 'Processing',
            self::Processed => 'Processed',
            self::Failed => 'Failed',
            self::DeadLetter => 'Dead Letter',
        };
    }
}
