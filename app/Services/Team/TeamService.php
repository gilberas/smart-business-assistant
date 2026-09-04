<?php

namespace App\Services\Team;

use App\Support\RoleRegistry;

class TeamService
{
    public function __construct(
        public readonly InvitationService $invitations,
        public readonly MembershipService $membership,
    ) {}

    public function availableRoles(): array
    {
        return RoleRegistry::tenantRoles();
    }

    public function roleLabel(string $role): string
    {
        return RoleRegistry::label($role);
    }

    public function roleHierarchy(): array
    {
        return RoleRegistry::hierarchy();
    }
}
