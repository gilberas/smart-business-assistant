<?php

namespace App\Enums;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('team.invitation_pending'),
            self::Accepted => __('team.invitation_accepted'),
            self::Cancelled => __('team.invitation_cancelled'),
            self::Expired => __('team.invitation_expired'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Accepted => 'green',
            self::Cancelled => 'red',
            self::Expired => 'gray',
        };
    }
}
