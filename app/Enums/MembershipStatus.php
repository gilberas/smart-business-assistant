<?php

namespace App\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('team.status_active'),
            self::Suspended => __('team.status_suspended'),
            self::Inactive => __('team.status_inactive'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'red',
            self::Inactive => 'gray',
        };
    }
}
