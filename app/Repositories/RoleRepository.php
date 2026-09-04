<?php

namespace App\Repositories;

use Spatie\Permission\Models\Role;

class RoleRepository
{
    public function all()
    {
        return Role::with('permissions')->orderBy('name')->get();
    }

    public function findById(int $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    public function findByName(string $name): ?Role
    {
        return Role::with('permissions')->where('name', $name)->first();
    }

    public function create(string $name, string $guardName = 'web'): Role
    {
        return Role::create(['name' => $name, 'guard_name' => $guardName]);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role->fresh();
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }

    public function findOrCreate(string $name, string $guardName = 'web'): Role
    {
        return Role::findOrCreate($name, $guardName);
    }
}
