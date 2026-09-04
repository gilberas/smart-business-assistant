<?php

namespace App\Enums\WhatsApp;

enum WhatsAppAccountStatus: string
{
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Error = 'error';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Connected => 'Connected',
            self::Disconnected => 'Disconnected',
            self::Error => 'Error',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Connected => 'green',
            self::Disconnected => 'gray',
            self::Error => 'red',
            self::Expired => 'yellow',
        };
    }
}
