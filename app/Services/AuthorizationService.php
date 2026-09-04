<?php

namespace App\Services;

use App\Contracts\AuthorizationServiceInterface;
use App\Managers\TenantManager;
use App\Models\Business;
use App\Models\User;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Exceptions\UnauthorizedException;

class AuthorizationService implements AuthorizationServiceInterface
{
    private const PERMISSION_CACHE_TTL = 3600;

    private const PERMISSION_CACHE_KEY = 'user_permissions_%d_%s';

    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    public function can(User $user, string $permission, ?Business $business = null): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $business = $business ?? $this->tenantManager->current();

        if (!$business) {
            return $this->checkGlobalPermission($user, $permission);
        }

        return $this->checkTenantPermission($user, $permission, $business);
    }

    public function cannot(User $user, string $permission, ?Business $business = null): bool
    {
        return !$this->can($user, $permission, $business);
    }

    public function canAny(User $user, array $permissions, ?Business $business = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($user, $permission, $business)) {
                return true;
            }
        }

        return false;
    }

    public function canAll(User $user, array $permissions, ?Business $business = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->cannot($user, $permission, $business)) {
                return false;
            }
        }

        return true;
    }

    public function role(User $user, ?Business $business = null): ?string
    {
        if ($this->isSuperAdmin($user)) {
            return RoleRegistry::SUPER_ADMIN;
        }

        $business = $business ?? $this->tenantManager->current();

        if (!$business) {
            return null;
        }

        return $this->getPivotRole($user, $business);
    }

    public function hasRole(User $user, string|array $roles, ?Business $business = null): bool
    {
        $currentRole = $this->role($user, $business);

        if (!$currentRole) {
            return false;
        }

        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($currentRole, $roles, true);
    }

    public function userPermissions(User $user, ?Business $business = null): array
    {
        if ($this->isSuperAdmin($user)) {
            return PermissionRegistry::all();
        }

        $business = $business ?? $this->tenantManager->current();

        if (!$business) {
            return $user->getAllPermissions()->pluck('name')->toArray();
        }

        return $this->getEffectivePermissions($user, $business);
    }

    public function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(RoleRegistry::SUPER_ADMIN);
    }

    public function isOwner(User $user, Business $business): bool
    {
        return $business->owner_id === $user->id;
    }

    public function isAdminOrAbove(User $user, Business $business): bool
    {
        $role = $this->getEffectiveRole($user, $business);

        return RoleRegistry::rank($role) >= RoleRegistry::rank(RoleRegistry::BUSINESS_ADMIN);
    }

    public function getEffectiveRole(User $user, Business $business): string
    {
        if ($this->isSuperAdmin($user)) {
            return RoleRegistry::SUPER_ADMIN;
        }

        return $this->getPivotRole($user, $business) ?? RoleRegistry::VIEWER;
    }

    public function getEffectivePermissions(User $user, Business $business): array
    {
        return $this->cachedPermissions($user, $business);
    }

    public function assertCan(User $user, string $permission, ?Business $business = null): void
    {
        if ($this->cannot($user, $permission, $business)) {
            throw UnauthorizedException::forPermissions([$permission]);
        }
    }

    public function flushPermissionCache(User $user, ?Business $business = null): void
    {
        $businessId = $business?->id ?? 'global';
        Cache::forget(sprintf(self::PERMISSION_CACHE_KEY, $user->id, $businessId));
    }

    private function checkGlobalPermission(User $user, string $permission): bool
    {
        return $user->can($permission);
    }

    private function checkTenantPermission(User $user, string $permission, Business $business): bool
    {
        $effectivePermissions = $this->getEffectivePermissions($user, $business);

        return in_array($permission, $effectivePermissions, true);
    }

    private function getPivotRole(User $user, Business $business): ?string
    {
        $pivot = $user->businesses()
            ->where('business_id', $business->id)
            ->first()
            ?->pivot;

        return $pivot?->role;
    }

    private function cachedPermissions(User $user, Business $business): array
    {
        $cacheKey = sprintf(self::PERMISSION_CACHE_KEY, $user->id, $business->id);

        return Cache::remember($cacheKey, self::PERMISSION_CACHE_TTL, function () use ($user, $business) {
            return $this->resolvePermissions($user, $business);
        });
    }

    private function resolvePermissions(User $user, Business $business): array
    {
        if ($this->isSuperAdmin($user)) {
            return PermissionRegistry::all();
        }

        $role = $this->getPivotRole($user, $business);

        if (!$role) {
            return [];
        }

        $defaultPermissions = RoleRegistry::defaultPermissions($role);

        $extraPermissions = $business->users()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot
            ->extra_permissions ?? [];

        return array_unique(array_merge($defaultPermissions, $extraPermissions));
    }
}
