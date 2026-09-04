<?php

namespace App\Http\Controllers\Api\V1\Authorization;

use App\Contracts\AuthorizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RoleService;
use App\Support\RoleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json([
            'roles' => $this->roleService->all(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $role = $this->roleService->findById($id);

        if (!$role) {
            return response()->json(['message' => __('authorization.role_not_found')], 404);
        }

        return response()->json([
            'role' => $role,
            'default_permissions' => $this->roleService->getDefaultPermissionsForRole($role->name),
        ]);
    }

    public function hierarchy(): JsonResponse
    {
        return response()->json([
            'hierarchy' => $this->roleService->getHierarchy(),
        ]);
    }

    public function assignToUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|in:' . implode(',', RoleRegistry::all()),
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        $role = $validated['role'];

        $currentUser = $request->user();

        if (!$this->roleService->canAssignRole($currentUser, $role, tenant())) {
            throw ValidationException::withMessages([
                'role' => [__('authorization.cannot_assign_role')],
            ]);
        }

        $targetUser->assignRole($role);

        return response()->json([
            'message' => __('authorization.role_assigned', ['role' => $role, 'user' => $targetUser->name]),
        ]);
    }

    public function removeFromUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);

        $targetUser->removeRole($validated['role']);

        return response()->json([
            'message' => __('authorization.role_removed'),
        ]);
    }

    public function userRoles(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }
}
