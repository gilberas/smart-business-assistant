<?php

namespace App\Contracts;

use App\Models\Business;
use App\Models\User;

interface AuthorizationServiceInterface
{
    public function can(User $user, string $permission, ?Business $business = null): bool;

    public function cannot(User $user, string $permission, ?Business $business = null): bool;

    public function canAny(User $user, array $permissions, ?Business $business = null): bool;

    public function canAll(User $user, array $permissions, ?Business $business = null): bool;

    public function role(User $user, ?Business $business = null): ?string;

    public function hasRole(User $user, string|array $roles, ?Business $business = null): bool;

    public function userPermissions(User $user, ?Business $business = null): array;

    public function isSuperAdmin(User $user): bool;

    public function isOwner(User $user, Business $business): bool;

    public function isAdminOrAbove(User $user, Business $business): bool;

    public function getEffectiveRole(User $user, Business $business): string;

    public function getEffectivePermissions(User $user, Business $business): array;

    public function assertCan(User $user, string $permission, ?Business $business = null): void;

    public function flushPermissionCache(User $user, ?Business $business = null): void;
}
