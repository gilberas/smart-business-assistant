<?php

namespace App\Policies;

use App\Models\User;
use App\Support\RoleRegistry;

class UserPolicy extends AuthorizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->can($user, 'team.view') || $this->isSuperAdmin($user);
    }

    public function view(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id
            || $this->can($user, 'team.view')
            || $this->isSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function update(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id
            || $this->isSuperAdmin($user);
    }

    public function delete(User $user, User $targetUser): bool
    {
        return $user->id === $targetUser->id
            || $this->isSuperAdmin($user);
    }

    public function assignRoles(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function managePermissions(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function viewRoles(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->hasRole($user, [RoleRegistry::BUSINESS_OWNER, RoleRegistry::BUSINESS_ADMIN]);
    }
}
