<?php

namespace App\Repositories;

use Spatie\Permission\Models\Permission;

class PermissionRepository
{
    public function all()
    {
        return Permission::orderBy('name')->get();
    }

    public function findById(int $id): ?Permission
    {
        return Permission::find($id);
    }

    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    public function create(string $name, string $guardName = 'web'): Permission
    {
        return Permission::create(['name' => $name, 'guard_name' => $guardName]);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->update($data);

        return $permission->fresh();
    }

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }

    public function findByNames(array $names)
    {
        return Permission::whereIn('name', $names)->get();
    }

    public function findOrCreate(string $name, string $guardName = 'web'): Permission
    {
        return Permission::findOrCreate($name, $guardName);
    }
}
