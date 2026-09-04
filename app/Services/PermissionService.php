<?php

namespace App\Services;

use App\Repositories\PermissionRepository;
use App\Support\PermissionRegistry;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepository $permissionRepository,
    ) {}

    public function all(): Collection
    {
        return $this->permissionRepository->all();
    }

    public function findById(int $id): ?Permission
    {
        return $this->permissionRepository->findById($id);
    }

    public function findByName(string $name): ?Permission
    {
        return $this->permissionRepository->findByName($name);
    }

    public function create(string $name, ?string $guardName = 'web'): Permission
    {
        return $this->permissionRepository->create($name, $guardName);
    }

    public function update(Permission $permission, array $data): Permission
    {
        return $this->permissionRepository->update($permission, $data);
    }

    public function delete(Permission $permission): void
    {
        $this->permissionRepository->delete($permission);
    }

    public function syncRegistry(): int
    {
        $created = 0;
        $registered = PermissionRegistry::all();

        foreach ($registered as $permissionName) {
            $existing = Permission::findByName($permissionName)->first();

            if (!$existing) {
                $this->create($permissionName);
                $created++;
            }
        }

        return $created;
    }

    public function getGroups(): array
    {
        return PermissionRegistry::groups();
    }

    public function getGroupFor(string $permission): ?string
    {
        return PermissionRegistry::groupFor($permission);
    }

    public function getLabel(string $permission): string
    {
        return PermissionRegistry::label($permission);
    }

    public function assignToRole(string $permission, string $role): void
    {
        $perm = $this->findByName($permission);
        $roleModel = Role::findByName($role);

        if ($perm && $roleModel) {
            $roleModel->givePermissionTo($perm);
        }
    }

    public function removeFromRole(string $permission, string $role): void
    {
        $perm = $this->findByName($permission);
        $roleModel = Role::findByName($role);

        if ($perm && $roleModel) {
            $roleModel->revokePermissionTo($perm);
        }
    }

    public function assignToUser(int|string $permission, int $userId): void
    {
        $perm = is_string($permission) ? $this->findByName($permission) : $this->findById($permission);
        $user = \App\Models\User::find($userId);

        if ($perm && $user) {
            $user->givePermissionTo($perm);
        }
    }

    public function syncRolePermissions(string $role, array $permissions): Role
    {
        $roleModel = Role::findByName($role);
        $roleModel->syncPermissions($permissions);

        return $roleModel;
    }
}
