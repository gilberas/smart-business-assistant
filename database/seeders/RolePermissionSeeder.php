<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'business.view', 'business.update', 'business.delete',
            'customer.view', 'customer.create', 'customer.update', 'customer.delete',
            'product.view', 'product.create', 'product.update', 'product.delete',
            'conversation.view', 'conversation.update',
            'message.view', 'message.create',
            'knowledge_base.view', 'knowledge_base.create', 'knowledge_base.update', 'knowledge_base.delete',
            'broadcast.view', 'broadcast.create', 'broadcast.update', 'broadcast.delete',
            'analytics.view',
            'settings.view', 'settings.update',
            'team.view', 'team.invite', 'team.remove',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->givePermissionTo($permissions);

        $agent = Role::findOrCreate('agent');
        $agent->givePermissionTo([
            'customer.view', 'customer.create', 'customer.update',
            'product.view', 'product.create', 'product.update',
            'conversation.view', 'conversation.update',
            'message.view', 'message.create',
            'knowledge_base.view', 'knowledge_base.create', 'knowledge_base.update',
            'analytics.view',
        ]);

        $viewer = Role::findOrCreate('viewer');
        $viewer->givePermissionTo([
            'customer.view',
            'product.view',
            'conversation.view',
            'message.view',
            'knowledge_base.view',
            'analytics.view',
        ]);
    }
}
