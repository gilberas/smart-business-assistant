<?php

namespace App\Http\Controllers\Api\V1\Authorization;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PermissionService;
use App\Support\PermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json([
            'permissions' => $this->permissionService->all(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $permission = $this->permissionService->findById($id);

        if (!$permission) {
            return response()->json(['message' => __('authorization.permission_not_found')], 404);
        }

        return response()->json([
            'permission' => $permission,
            'group' => $this->permissionService->getGroupFor($permission->name),
            'label' => $this->permissionService->getLabel($permission->name),
        ]);
    }

    public function sync(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $created = $this->permissionService->syncRegistry();

        return response()->json([
            'message' => __('authorization.permissions_synced', ['count' => $created]),
            'created' => $created,
        ]);
    }

    public function groups(): JsonResponse
    {
        return response()->json([
            'groups' => $this->permissionService->getGroups(),
        ]);
    }

    public function registry(): JsonResponse
    {
        $groups = PermissionRegistry::groups();
        $result = [];

        foreach ($groups as $group => $permissions) {
            $result[] = [
                'group' => $group,
                'permissions' => array_map(fn ($p) => [
                    'name' => $p,
                    'label' => PermissionRegistry::label($p),
                ], $permissions),
            ];
        }

        return response()->json(['groups' => $result]);
    }
}
