<?php

namespace App\Http\Controllers\Api\V1\Team;

use App\Contracts\AuthorizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Team\ChangeRoleRequest;
use App\Http\Requests\Team\InviteMemberRequest;
use App\Http\Requests\Team\TransferOwnershipRequest;
use App\Http\Resources\Team\InvitationResource;
use App\Http\Resources\Team\MemberResource;
use App\Http\Resources\Team\MembershipActivityResource;
use App\Models\Business;
use App\Models\User;
use App\Services\Team\InvitationService;
use App\Services\Team\MembershipService;
use App\Services\Team\TeamService;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function __construct(
        private readonly TeamService $teamService,
        private readonly InvitationService $invitationService,
        private readonly MembershipService $membershipService,
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    private function getBusiness(): Business
    {
        $business = tenant();

        if (!$business) {
            abort(403, __('tenant.no_business_selected'));
        }

        return $business;
    }

    public function invite(InviteMemberRequest $request): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_INVITE, $business)) {
            abort(403, __('team.unauthorized_invite'));
        }

        $invitation = $this->invitationService->invite(
            $request->user(),
            $business,
            $request->input('email'),
            $request->input('role'),
            $request->input('message'),
        );

        return response()->json([
            'invitation' => new InvitationResource($invitation),
            'message' => __('team.invitation_sent'),
        ], 201);
    }

    public function invitations(Request $request): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_VIEW, $business)) {
            abort(403);
        }

        return response()->json([
            'invitations' => InvitationResource::collection(
                $this->invitationService->pending($business)
            ),
        ]);
    }

    public function cancelInvitation(Request $request, int $invitationId): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_INVITE, $business)) {
            abort(403);
        }

        $invitation = $business->invitations()->findOrFail($invitationId);

        $this->invitationService->cancel($invitation, $request->user());

        return response()->json(['message' => __('team.invitation_cancelled')]);
    }

    public function resendInvitation(Request $request, int $invitationId): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_INVITE, $business)) {
            abort(403);
        }

        $invitation = $business->invitations()->findOrFail($invitationId);

        $this->invitationService->resend($invitation);

        return response()->json(['message' => __('team.invitation_resent')]);
    }

    public function acceptInvitation(string $token): JsonResponse
    {
        $invitation = $this->invitationService->findByToken($token);

        if (!$invitation) {
            return response()->json(['message' => __('team.invalid_invitation')], 404);
        }

        $user = request()->user();

        if ($user && $user->email !== $invitation->email) {
            return response()->json(['message' => __('team.invitation_email_mismatch')], 403);
        }

        if (!$user) {
            $user = User::where('email', $invitation->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => __('team.invitation_requires_account'),
                    'requires_registration' => true,
                    'email' => $invitation->email,
                ], 422);
            }
        }

        $this->invitationService->accept($token, $user);

        return response()->json([
            'message' => __('team.invitation_accepted'),
            'business' => [
                'id' => $invitation->business_id,
                'name' => $invitation->business->name,
            ],
        ]);
    }

    public function rejectInvitation(string $token): JsonResponse
    {
        $invitation = $this->invitationService->findByToken($token);

        if (!$invitation) {
            return response()->json(['message' => __('team.invalid_invitation')], 404);
        }

        if (!$invitation->isPending()) {
            return response()->json(['message' => __('team.invitation_not_pending')], 422);
        }

        $invitation->markAsCancelled();

        return response()->json(['message' => __('team.invitation_rejected')]);
    }

    public function members(Request $request): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_VIEW, $business)) {
            abort(403);
        }

        return response()->json([
            'members' => MemberResource::collection(
                $this->membershipService->members($business)
            ),
        ]);
    }

    public function changeRole(ChangeRoleRequest $request, int $memberId): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_ROLE_UPDATE, $business)) {
            abort(403, __('team.unauthorized_role_change'));
        }

        $member = User::findOrFail($memberId);
        $newRole = $request->input('role');

        $actorRole = $this->authorizationService->getEffectiveRole($request->user(), $business);

        if (!RoleRegistry::canManage($actorRole, $newRole)) {
            throw ValidationException::withMessages([
                'role' => [__('team.cannot_assign_higher_role')],
            ]);
        }

        $this->membershipService->changeRole($business, $member, $request->user(), $newRole);

        return response()->json(['message' => __('team.role_updated')]);
    }

    public function removeMember(Request $request, int $memberId): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_REMOVE, $business)) {
            abort(403, __('team.unauthorized_remove'));
        }

        $member = User::findOrFail($memberId);

        $this->membershipService->removeMember($business, $member, $request->user());

        return response()->json(['message' => __('team.member_removed')]);
    }

    public function suspendMember(Request $request, int $memberId): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_REMOVE, $business)) {
            abort(403);
        }

        $member = User::findOrFail($memberId);

        $this->membershipService->suspend($business, $member, $request->user());

        return response()->json(['message' => __('team.member_suspended')]);
    }

    public function restoreMember(Request $request, int $memberId): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_REMOVE, $business)) {
            abort(403);
        }

        $member = User::findOrFail($memberId);

        $this->membershipService->restore($business, $member, $request->user());

        return response()->json(['message' => __('team.member_restored')]);
    }

    public function transferOwnership(TransferOwnershipRequest $request): JsonResponse
    {
        $business = $this->getBusiness();

        if (!$this->authorizationService->isOwner($request->user(), $business)) {
            abort(403, __('team.only_owner_transfer'));
        }

        $newOwner = User::findOrFail($request->input('user_id'));

        $this->membershipService->transferOwnership($business, $newOwner, $request->user());

        return response()->json(['message' => __('team.ownership_transferred')]);
    }

    public function leave(Request $request): JsonResponse
    {
        $business = $this->getBusiness();

        $this->membershipService->leaveBusiness($business, $request->user());

        return response()->json(['message' => __('team.left_business')]);
    }

    public function activity(Request $request): JsonResponse
    {
        $business = $this->getBusiness();

        if ($this->authorizationService->cannot($request->user(), PermissionRegistry::TEAM_VIEW, $business)) {
            abort(403);
        }

        return response()->json([
            'activity' => MembershipActivityResource::collection(
                $this->membershipService->activity($business)
            ),
        ]);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'roles' => collect($this->teamService->availableRoles())->map(fn ($role) => [
                'name' => $role,
                'label' => $this->teamService->roleLabel($role),
                'hierarchy' => $this->teamService->roleHierarchy()[$role] ?? 0,
            ])->values(),
        ]);
    }
}
