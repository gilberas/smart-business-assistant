<?php

namespace App\Enums\Conversation;

enum ConversationSource: string
{
    case WhatsApp = 'whatsapp';
    case Telegram = 'telegram';
    case Messenger = 'messenger';
    case Instagram = 'instagram';
    case Sms = 'sms';
    case Email = 'email';
    case Web = 'web';
    case Api = 'api';
    case Internal = 'internal';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Telegram => 'Telegram',
            self::Messenger => 'Messenger',
            self::Instagram => 'Instagram',
            self::Sms => 'SMS',
            self::Email => 'Email',
            self::Web => 'Web',
            self::Api => 'API',
            self::Internal => 'Internal',
        };
    }
}
