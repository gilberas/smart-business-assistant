<?php

if (!function_exists('can')) {
    function can(string $permission, ?\App\Models\Business $business = null): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return app(\App\Contracts\AuthorizationServiceInterface::class)->can($user, $permission, $business);
    }
}

if (!function_exists('cannot')) {
    function cannot(string $permission, ?\App\Models\Business $business = null): bool
    {
        return !can($permission, $business);
    }
}

if (!function_exists('can_any')) {
    function can_any(array $permissions, ?\App\Models\Business $business = null): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return app(\App\Contracts\AuthorizationServiceInterface::class)->canAny($user, $permissions, $business);
    }
}

if (!function_exists('can_all')) {
    function can_all(array $permissions, ?\App\Models\Business $business = null): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return app(\App\Contracts\AuthorizationServiceInterface::class)->canAll($user, $permissions, $business);
    }
}

if (!function_exists('has_role')) {
    function has_role(string|array $roles, ?\App\Models\Business $business = null): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return app(\App\Contracts\AuthorizationServiceInterface::class)->hasRole($user, $roles, $business);
    }
}

if (!function_exists('current_role')) {
    function current_role(?\App\Models\Business $business = null): ?string
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        return app(\App\Contracts\AuthorizationServiceInterface::class)->role($user, $business);
    }
}

if (!function_exists('is_super_admin')) {
    function is_super_admin(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        return app(\App\Contracts\AuthorizationServiceInterface::class)->isSuperAdmin($user);
    }
}

if (!function_exists('assert_can')) {
    function assert_can(string $permission, ?\App\Models\Business $business = null): void
    {
        $user = auth()->user();

        if (!$user) {
            abort(401);
        }

        app(\App\Contracts\AuthorizationServiceInterface::class)->assertCan($user, $permission, $business);
    }
}
