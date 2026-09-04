<?php

namespace App\Http\Middleware;

use App\Contracts\AuthorizationServiceInterface;
use App\Managers\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessSelected
{
    public function __construct(
        private readonly TenantManager $tenantManager,
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admins have unrestricted, cross-tenant access and are not
        // required to have a "current business" selected to use the platform.
        if ($user && $this->authorizationService->isSuperAdmin($user)) {
            return $next($request);
        }

        if ($user && !$this->tenantManager->current()) {
            return response()->json([
                'message' => __('tenant.no_business_selected'),
                'requires_business' => true,
            ], 403);
        }

        return $next($request);
    }
}
