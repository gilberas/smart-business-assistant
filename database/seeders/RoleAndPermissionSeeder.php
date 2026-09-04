<?php

namespace Database\Seeders;

use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->createRoles();
        $this->assignPermissionsToRoles();
    }

    private function createPermissions(): void
    {
        $registered = PermissionRegistry::all();

        foreach ($registered as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    private function createRoles(): void
    {
        $roles = RoleRegistry::all();

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    private function assignPermissionsToRoles(): void
    {
        $roles = RoleRegistry::all();

        foreach ($roles as $role) {
            $roleModel = Role::findByName($role);
            $permissions = RoleRegistry::defaultPermissions($role);

            $permissionModels = Permission::whereIn('name', $permissions)->get();
            $roleModel->syncPermissions($permissionModels);
        }
    }
}
