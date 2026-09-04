<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Http\Controllers\Controller;
use App\Http\Requests\Business\CreateBusinessRequest;
use App\Http\Requests\Business\SwitchBusinessRequest;
use App\Http\Requests\Business\UpdateBusinessRequest;
use App\Http\Resources\Business\BusinessResource;
use App\Services\BusinessService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BusinessController extends Controller
{
    public function __construct(
        private readonly BusinessService $businessService,
        private readonly TenantService $tenantService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businesses = $this->businessService->list($request->user());

        return response()->json([
            'businesses' => BusinessResource::collection($businesses),
            'current_business_id' => $this->tenantService->currentId(),
        ]);
    }

    public function store(CreateBusinessRequest $request): JsonResponse
    {
        try {
            $business = $this->businessService->create($request->user(), $request->validated());

            return response()->json([
                'business' => new BusinessResource($business),
                'message' => __('business.created'),
            ], 201);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('business.creation_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('business.try_again'),
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $id);

        $business->load('settings');

        return response()->json(new BusinessResource($business));
    }

    public function update(UpdateBusinessRequest $request, int $id): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $id);

        $business = $this->businessService->update($request->user(), $business, $request->validated());

        return response()->json([
            'business' => new BusinessResource($business),
            'message' => __('business.updated'),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $business = $this->tenantService->findBusiness($request->user(), $id);

        $this->businessService->delete($request->user(), $business);

        return response()->json(['message' => __('business.deleted')]);
    }

    public function current(Request $request): JsonResponse
    {
        $business = $this->tenantService->current();

        if (!$business) {
            return response()->json([
                'message' => __('tenant.no_business_selected'),
                'requires_business' => true,
            ], 404);
        }

        $business->load('settings');

        $memberRole = $this->tenantService->memberRole($business, $request->user());

        return response()->json(
            (new BusinessResource($business))->additional([
                'member_role' => $memberRole,
            ])
        );
    }

    public function switch(SwitchBusinessRequest $request): JsonResponse
    {
        $business = \App\Models\Business::findOrFail($request->input('business_id'));

        $this->tenantService->switch($request->user(), $business);

        $business->load('settings');

        $memberRole = $this->tenantService->memberRole($business, $request->user());

        return response()->json([
            'business' => new BusinessResource($business),
            'member_role' => $memberRole,
            'message' => __('business.switched'),
        ]);
    }
}
