<?php

use App\Contracts\AuthorizationServiceInterface;
use App\Models\Business;
use App\Models\User;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    $this->authorizationService = app(AuthorizationServiceInterface::class);
});

it('registers all permissions from registry', function () {
    $permissionNames = \Spatie\Permission\Models\Permission::pluck('name')->toArray();
    $allPermissions = PermissionRegistry::all();

    foreach ($allPermissions as $permission) {
        expect(in_array($permission, $permissionNames))->toBeTrue("Permission '$permission' not found in database");
    }

    expect(\Spatie\Permission\Models\Permission::count())->toBe(count(PermissionRegistry::all()));
});

it('registers all roles from registry', function () {
    $roleNames = \Spatie\Permission\Models\Role::pluck('name')->toArray();

    foreach (RoleRegistry::all() as $role) {
        expect(in_array($role, $roleNames))->toBeTrue("Role '$role' not found in database");
    }

    expect(\Spatie\Permission\Models\Role::count())->toBe(count(RoleRegistry::all()));
});

it('assigns correct permissions to each role', function () {
    foreach (RoleRegistry::all() as $role) {
        $roleModel = \Spatie\Permission\Models\Role::findByName($role);
        $expectedPermissions = RoleRegistry::defaultPermissions($role);
        $actualPermissions = $roleModel->permissions->pluck('name')->toArray();

        foreach ($expectedPermissions as $permission) {
            expect(in_array($permission, $actualPermissions))
                ->toBeTrue("Role '$role' missing permission '$permission'");
        }
    }
});

it('super admin has all permissions', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleRegistry::SUPER_ADMIN);

    expect($this->authorizationService->isSuperAdmin($user))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::SUPER_ADMIN))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::BUSINESS_DELETE))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::CUSTOMERS_DELETE))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::SETTINGS_MANAGE))->toBeTrue();

    expect($this->authorizationService->role($user))->toBe(RoleRegistry::SUPER_ADMIN);
});

it('super admin bypasses all permission checks', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleRegistry::SUPER_ADMIN);

    expect($this->authorizationService->can($user, 'nonexistent.permission'))->toBeTrue();
});

it('regular user without roles has no permissions', function () {
    $user = User::factory()->create();

    expect($this->authorizationService->isSuperAdmin($user))->toBeFalse();
    expect($this->authorizationService->can($user, PermissionRegistry::BUSINESS_VIEW))->toBeFalse();
    expect($this->authorizationService->role($user))->toBeNull();
});

it('evaluates tenant permissions based on pivot role', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $user->id]);
    $user->businesses()->attach($business->id, [
        'role' => 'manager',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    expect($this->authorizationService->can($user, PermissionRegistry::CUSTOMERS_VIEW, $business))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::CUSTOMERS_CREATE, $business))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::CUSTOMERS_UPDATE, $business))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::CUSTOMERS_DELETE, $business))->toBeFalse();
    expect($this->authorizationService->can($user, PermissionRegistry::SETTINGS_MANAGE, $business))->toBeFalse();
});

it('business owner has all tenant permissions', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $user->id]);
    $user->businesses()->attach($business->id, [
        'role' => 'business-owner',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $ownerPermissions = RoleRegistry::defaultPermissions(RoleRegistry::BUSINESS_OWNER);

    foreach ($ownerPermissions as $permission) {
        expect($this->authorizationService->can($user, $permission, $business))
            ->toBeTrue("Owner missing permission: $permission");
    }
});

it('viewer has only view permissions within business', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();
    $user->businesses()->attach($business->id, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    expect($this->authorizationService->can($user, PermissionRegistry::BUSINESS_VIEW, $business))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::CUSTOMERS_VIEW, $business))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::PRODUCTS_VIEW, $business))->toBeTrue();

    expect($this->authorizationService->can($user, PermissionRegistry::CUSTOMERS_CREATE, $business))->toBeFalse();
    expect($this->authorizationService->can($user, PermissionRegistry::CUSTOMERS_DELETE, $business))->toBeFalse();
    expect($this->authorizationService->can($user, PermissionRegistry::BUSINESS_DELETE, $business))->toBeFalse();
    expect($this->authorizationService->can($user, PermissionRegistry::SETTINGS_MANAGE, $business))->toBeFalse();
});

it('user has different roles in different businesses', function () {
    $user = User::factory()->create();
    $businessA = Business::factory()->create(['owner_id' => $user->id]);
    $businessB = Business::factory()->create();

    $user->businesses()->attach($businessA->id, ['role' => 'business-owner', 'status' => 'active', 'joined_at' => now()]);
    $user->businesses()->attach($businessB->id, ['role' => 'agent', 'status' => 'active', 'joined_at' => now()]);

    expect($this->authorizationService->getEffectiveRole($user, $businessA))->toBe('business-owner');
    expect($this->authorizationService->getEffectiveRole($user, $businessB))->toBe('agent');

    expect($this->authorizationService->can($user, PermissionRegistry::SETTINGS_MANAGE, $businessA))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::SETTINGS_MANAGE, $businessB))->toBeFalse();
});

it('canAny returns true if any permission matches', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionRegistry::CUSTOMERS_VIEW);

    expect($this->authorizationService->canAny($user, [
        PermissionRegistry::CUSTOMERS_VIEW,
        PermissionRegistry::CUSTOMERS_DELETE,
    ]))->toBeTrue();

    expect($this->authorizationService->canAny($user, [
        PermissionRegistry::CUSTOMERS_DELETE,
        PermissionRegistry::PRODUCTS_DELETE,
    ]))->toBeFalse();
});

it('canAll returns true only if all permissions match', function () {
    $user = User::factory()->create();
    $user->givePermissionTo([PermissionRegistry::CUSTOMERS_VIEW, PermissionRegistry::CUSTOMERS_CREATE]);

    expect($this->authorizationService->canAll($user, [
        PermissionRegistry::CUSTOMERS_VIEW,
        PermissionRegistry::CUSTOMERS_CREATE,
    ]))->toBeTrue();

    expect($this->authorizationService->canAll($user, [
        PermissionRegistry::CUSTOMERS_VIEW,
        PermissionRegistry::CUSTOMERS_DELETE,
    ]))->toBeFalse();
});

it('isAdminOrAbove returns true for owner and admin roles', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $user->id]);

    $user->businesses()->attach($business->id, ['role' => 'business-owner', 'status' => 'active', 'joined_at' => now()]);
    expect($this->authorizationService->isAdminOrAbove($user, $business))->toBeTrue();

    $user->businesses()->updateExistingPivot($business->id, ['role' => 'business-admin']);
    expect($this->authorizationService->isAdminOrAbove($user, $business))->toBeTrue();

    $user->businesses()->updateExistingPivot($business->id, ['role' => 'manager']);
    expect($this->authorizationService->isAdminOrAbove($user, $business))->toBeFalse();
});

it('isOwner returns true only for the business owner', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);

    $notOwner = User::factory()->create();

    expect($this->authorizationService->isOwner($owner, $business))->toBeTrue();
    expect($this->authorizationService->isOwner($notOwner, $business))->toBeFalse();
});

it('hasRole works correctly', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleRegistry::SUPER_ADMIN);

    expect($this->authorizationService->hasRole($user, RoleRegistry::SUPER_ADMIN))->toBeTrue();
    expect($this->authorizationService->hasRole($user, RoleRegistry::AGENT))->toBeFalse();
    expect($this->authorizationService->hasRole($user, [RoleRegistry::SUPER_ADMIN, RoleRegistry::AGENT]))->toBeTrue();
});

it('userPermissions returns all permissions for super admin', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleRegistry::SUPER_ADMIN);

    $permissions = $this->authorizationService->userPermissions($user);
    expect($permissions)->toMatchArray(PermissionRegistry::all());
});

it('assertCan throws for unauthorized permission', function () {
    $user = User::factory()->create();
    $this->authorizationService->assertCan($user, PermissionRegistry::BUSINESS_VIEW);
})->throws(\Spatie\Permission\Exceptions\UnauthorizedException::class);

it('permission registry has valid groups', function () {
    $groups = PermissionRegistry::groups();

    $allFromGroups = [];
    foreach ($groups as $group => $permissions) {
        expect($group)->toBeString();
        foreach ($permissions as $permission) {
            $allFromGroups[] = $permission;
        }
    }

    $allPermissions = PermissionRegistry::all();
    expect($allFromGroups)->toMatchArray($allPermissions);
});

it('role hierarchy is correctly ordered', function () {
    $hierarchy = RoleRegistry::hierarchy();

    expect($hierarchy[RoleRegistry::SUPER_ADMIN])->toBeGreaterThan($hierarchy[RoleRegistry::BUSINESS_OWNER]);
    expect($hierarchy[RoleRegistry::BUSINESS_OWNER])->toBeGreaterThan($hierarchy[RoleRegistry::BUSINESS_ADMIN]);
    expect($hierarchy[RoleRegistry::BUSINESS_ADMIN])->toBeGreaterThan($hierarchy[RoleRegistry::MANAGER]);
    expect($hierarchy[RoleRegistry::MANAGER])->toBeGreaterThan($hierarchy[RoleRegistry::AGENT]);
    expect($hierarchy[RoleRegistry::AGENT])->toBeGreaterThan($hierarchy[RoleRegistry::VIEWER]);
});

it('role management respects hierarchy', function () {
    expect(RoleRegistry::canManage(RoleRegistry::BUSINESS_OWNER, RoleRegistry::AGENT))->toBeTrue();
    expect(RoleRegistry::canManage(RoleRegistry::AGENT, RoleRegistry::BUSINESS_OWNER))->toBeFalse();
    expect(RoleRegistry::canManage(RoleRegistry::BUSINESS_ADMIN, RoleRegistry::MANAGER))->toBeTrue();
    expect(RoleRegistry::canManage(RoleRegistry::MANAGER, RoleRegistry::MANAGER))->toBeFalse();
});

it('permission registry labels are unique', function () {
    $labels = [];
    foreach (PermissionRegistry::all() as $permission) {
        $label = PermissionRegistry::label($permission);
        expect(in_array($label, $labels))->toBeFalse("Duplicate label: $label");
        $labels[] = $label;
    }
});

it('role labels and descriptions are defined for all roles', function () {
    foreach (RoleRegistry::all() as $role) {
        $label = RoleRegistry::label($role);
        $description = RoleRegistry::description($role);

        expect($label)->not->toBe($role);
        expect($description)->not->toBeEmpty();
    }
});

it('prevents cross-tenant access', function () {
    $user = User::factory()->create();
    $businessA = Business::factory()->create(['owner_id' => $user->id]);
    $businessB = Business::factory()->create();

    $user->businesses()->attach($businessA->id, ['role' => 'business-admin', 'status' => 'active', 'joined_at' => now()]);

    $userInB = User::factory()->create();
    $businessB->users()->attach($userInB->id, ['role' => 'agent', 'status' => 'active', 'joined_at' => now()]);

    expect($this->authorizationService->can($user, PermissionRegistry::SETTINGS_MANAGE, $businessA))->toBeTrue();
    expect($this->authorizationService->can($user, PermissionRegistry::SETTINGS_MANAGE, $businessB))->toBeFalse();
    expect($this->authorizationService->getEffectiveRole($user, $businessB))->toBe('viewer');
});

it('flushes permission cache when a member role changes', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $business->users()->attach($owner->id, ['role' => 'business-owner', 'status' => 'active', 'joined_at' => now()]);

    $member = User::factory()->create();
    $business->users()->attach($member->id, ['role' => 'viewer', 'status' => 'active', 'joined_at' => now()]);

    expect($this->authorizationService->can($member, PermissionRegistry::TEAM_INVITE, $business))->toBeFalse();

    $this->actingAs($owner);
    app(\App\Managers\TenantManager::class)->switch($owner, $business);

    $this->putJson("/api/v1/team/members/{$member->id}/role", ['role' => 'business-admin'])->assertStatus(200);

    expect($this->authorizationService->can($member, PermissionRegistry::TEAM_INVITE, $business))->toBeTrue();
    expect($this->authorizationService->can($member, PermissionRegistry::TEAM_REMOVE, $business))->toBeTrue();
});

it('flushes permission cache when a member is removed', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create(['owner_id' => $owner->id]);
    $business->users()->attach($owner->id, ['role' => 'business-owner', 'status' => 'active', 'joined_at' => now()]);

    $member = User::factory()->create();
    $business->users()->attach($member->id, ['role' => 'viewer', 'status' => 'active', 'joined_at' => now()]);

    expect($this->authorizationService->can($member, PermissionRegistry::CUSTOMERS_VIEW, $business))->toBeTrue();

    $this->actingAs($owner);
    app(\App\Managers\TenantManager::class)->switch($owner, $business);

    $this->deleteJson("/api/v1/team/members/{$member->id}")->assertStatus(200);

    expect($this->authorizationService->can($member, PermissionRegistry::CUSTOMERS_VIEW, $business))->toBeFalse();
});
