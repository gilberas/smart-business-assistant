<?php

namespace App\Http\Middleware;

use App\Contracts\AuthorizationServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        foreach ($permissions as $permission) {
            if ($this->authorizationService->cannot($user, $permission)) {
                abort(403, __('authorization.missing_permission', ['permission' => $permission]));
            }
        }

        return $next($request);
    }
}
