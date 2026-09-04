<?php

namespace App\Policies;

use App\Contracts\AuthorizationServiceInterface;
use App\Models\User;

abstract class AuthorizationPolicy
{
    public function __construct(
        protected readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    protected function can(User $user, string $permission): bool
    {
        return $this->authorizationService->can($user, $permission);
    }

    protected function isSuperAdmin(User $user): bool
    {
        return $this->authorizationService->isSuperAdmin($user);
    }

    protected function hasRole(User $user, string|array $roles): bool
    {
        return $this->authorizationService->hasRole($user, $roles);
    }
}
