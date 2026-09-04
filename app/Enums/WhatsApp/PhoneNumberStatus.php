<?php

namespace App\Enums\WhatsApp;

enum PhoneNumberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Flagged = 'flagged';
    case Disabled = 'disabled';
    case ReconnectRequired = 'reconnect_required';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Flagged => 'Flagged',
            self::Disabled => 'Disabled',
            self::ReconnectRequired => 'Reconnect Required',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Active => 'green',
            self::Flagged => 'yellow',
            self::Disabled => 'red',
            self::ReconnectRequired => 'orange',
        };
    }
}
