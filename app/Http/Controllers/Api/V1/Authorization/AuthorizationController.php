<?php

namespace App\Http\Controllers\Api\V1\Authorization;

use App\Contracts\AuthorizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use App\Services\RoleService;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorizationController extends Controller
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorizationService,
        private readonly PermissionService $permissionService,
        private readonly RoleService $roleService,
    ) {}

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'permissions' => $this->authorizationService->userPermissions($user),
            'role' => $this->authorizationService->role($user),
            'is_super_admin' => $this->authorizationService->isSuperAdmin($user),
        ]);
    }

    public function permissions(): JsonResponse
    {
        return response()->json([
            'permissions' => $this->permissionService->all(),
            'groups' => $this->permissionService->getGroups(),
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'roles' => $this->roleService->all(),
            'tenant_roles' => $this->roleService->allTenantRoles(),
            'hierarchy' => $this->roleService->getHierarchy(),
        ]);
    }

    public function registry(): JsonResponse
    {
        $groups = PermissionRegistry::groups();
        $formatted = [];

        foreach ($groups as $group => $permissions) {
            $formatted[] = [
                'group' => $group,
                'permissions' => array_map(fn ($p) => [
                    'name' => $p,
                    'label' => PermissionRegistry::label($p),
                ], $permissions),
            ];
        }

        return response()->json([
            'roles' => array_map(fn ($role) => [
                'name' => $role,
                'label' => RoleRegistry::label($role),
                'description' => RoleRegistry::description($role),
                'permissions' => RoleRegistry::defaultPermissions($role),
            ], RoleRegistry::all()),
            'permission_groups' => $formatted,
        ]);
    }
}
