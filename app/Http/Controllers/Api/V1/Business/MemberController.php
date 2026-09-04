<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\AddMemberRequest;
use App\Http\Requests\Business\UpdateMemberRoleRequest;
use App\Http\Resources\Business\MemberResource;
use App\Services\BusinessService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(
        private readonly BusinessService $businessService,
        private readonly TenantService $tenantService,
    ) {}

    public function index(Request $request, int $businessId): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $businessId);

        $members = $this->businessService->members($business);

        return response()->json([
            'members' => MemberResource::collection($members),
        ]);
    }

    public function store(AddMemberRequest $request, int $businessId): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $businessId);

        $member = \App\Models\User::where('email', $request->input('email'))->firstOrFail();

        $this->businessService->addMember(
            $request->user(),
            $business,
            $member,
            $request->input('role', 'agent'),
        );

        return response()->json([
            'member' => new MemberResource($member),
            'message' => __('business.member_added'),
        ], 201);
    }

    public function update(UpdateMemberRoleRequest $request, int $businessId, int $memberId): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $businessId);
        $member = \App\Models\User::findOrFail($memberId);

        $this->businessService->updateMemberRole(
            $request->user(),
            $business,
            $member,
            $request->input('role'),
        );

        return response()->json([
            'message' => __('business.member_role_updated'),
        ]);
    }

    public function destroy(Request $request, int $businessId, int $memberId): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $businessId);
        $member = \App\Models\User::findOrFail($memberId);

        $this->businessService->removeMember($request->user(), $business, $member);

        return response()->json(['message' => __('business.member_removed')]);
    }
}
