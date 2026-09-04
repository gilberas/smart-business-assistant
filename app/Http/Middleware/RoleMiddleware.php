<?php

namespace App\Http\Middleware;

use App\Contracts\AuthorizationServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if (!$this->authorizationService->hasRole($user, $roles)) {
            abort(403, __('authorization.required_role'));
        }

        return $next($request);
    }
}
