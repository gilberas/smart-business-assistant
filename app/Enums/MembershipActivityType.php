<?php

namespace App\Enums;

enum MembershipActivityType: string
{
    case Invited = 'invited';
    case InvitationAccepted = 'invitation_accepted';
    case Joined = 'joined';
    case RoleChanged = 'role_changed';
    case Suspended = 'suspended';
    case Restored = 'restored';
    case Removed = 'removed';
    case Left = 'left';
    case OwnershipTransferred = 'ownership_transferred';

    public function label(): string
    {
        return match ($this) {
            self::Invited => __('team.activity_invited'),
            self::InvitationAccepted => __('team.activity_invitation_accepted'),
            self::Joined => __('team.activity_joined'),
            self::RoleChanged => __('team.activity_role_changed'),
            self::Suspended => __('team.activity_suspended'),
            self::Restored => __('team.activity_restored'),
            self::Removed => __('team.activity_removed'),
            self::Left => __('team.activity_left'),
            self::OwnershipTransferred => __('team.activity_ownership_transferred'),
        };
    }
}
