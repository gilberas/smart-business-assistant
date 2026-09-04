<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Contracts\AuthorizationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Business\UpdateOnboardingRequest;
use App\Http\Resources\Business\BusinessResource;
use App\Models\Business;
use App\Support\PermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BusinessOnboardingController extends Controller
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function update(UpdateOnboardingRequest $request, Business $business): JsonResponse
    {
        try {
            if ($this->authorizationService->cannot($request->user(), PermissionRegistry::BUSINESS_UPDATE, $business)) {
                abort(403);
            }

            $business->update([
                'onboarding_step' => $request->validated('step'),
                'onboarding_completed_at' => null,
            ]);

            return response()->json([
                'business' => new BusinessResource($business->fresh()),
                'message' => __('business.onboarding_updated'),
            ]);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('business.onboarding_update_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('business.try_again'),
            ], 500);
        }
    }

    public function complete(Request $request, Business $business): JsonResponse
    {
        try {
            if ($this->authorizationService->cannot($request->user(), PermissionRegistry::BUSINESS_UPDATE, $business)) {
                abort(403);
            }

            $business->update([
                'onboarding_completed_at' => now(),
            ]);

            return response()->json([
                'business' => new BusinessResource($business->fresh()),
                'message' => __('business.onboarding_completed'),
            ]);
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('business.onboarding_complete_failed'),
                'error' => config('app.debug') ? $e->getMessage() : __('business.try_again'),
            ], 500);
        }
    }
}
