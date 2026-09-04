<?php

namespace App\Services;

use App\Contracts\AuthorizationServiceInterface;
use App\Models\Business;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Support\RoleRegistry;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function all(): Collection
    {
        return $this->roleRepository->all();
    }

    public function findById(int $id): ?Role
    {
        return $this->roleRepository->findById($id);
    }

    public function findByName(string $name): ?Role
    {
        return $this->roleRepository->findByName($name);
    }

    public function create(string $name, ?string $guardName = 'web'): Role
    {
        return $this->roleRepository->create($name, $guardName);
    }

    public function update(Role $role, array $data): Role
    {
        return $this->roleRepository->update($role, $data);
    }

    public function delete(Role $role): void
    {
        $this->roleRepository->delete($role);
    }

    public function allTenantRoles(): array
    {
        return RoleRegistry::tenantRoles();
    }

    public function getHierarchy(): array
    {
        return RoleRegistry::hierarchy();
    }

    public function getDefaultPermissionsForRole(string $role): array
    {
        return RoleRegistry::defaultPermissions($role);
    }

    public function assignBusinessRole(Business $business, User $user, string $role): void
    {
        $tenantRoles = RoleRegistry::tenantRoles();

        if (!in_array($role, $tenantRoles, true)) {
            throw ValidationException::withMessages([
                'role' => [__('authorization.invalid_role')],
            ]);
        }

        $business->users()->syncWithoutDetaching([
            $user->id => ['role' => $role],
        ]);
    }

    public function updateBusinessRole(Business $business, User $user, string $role): void
    {
        $tenantRoles = RoleRegistry::tenantRoles();

        if (!in_array($role, $tenantRoles, true)) {
            throw ValidationException::withMessages([
                'role' => [__('authorization.invalid_role')],
            ]);
        }

        if ($user->id === $business->owner_id && $role !== RoleRegistry::BUSINESS_OWNER) {
            throw ValidationException::withMessages([
                'role' => [__('authorization.cannot_change_owner_role')],
            ]);
        }

        $business->users()->updateExistingPivot($user->id, ['role' => $role]);
    }

    public function canAssignRole(User $actor, string $targetRole, Business $business): bool
    {
        if ($this->authorizationService->isSuperAdmin($actor)) {
            return true;
        }

        $actorRole = $this->authorizationService->getEffectiveRole($actor, $business);

        return RoleRegistry::canManage($actorRole, $targetRole);
    }

    public function label(string $role): string
    {
        return RoleRegistry::label($role);
    }

    public function description(string $role): string
    {
        return RoleRegistry::description($role);
    }
}
