<?php

if (!function_exists('tenant')) {
    function tenant(): ?\App\Models\Business
    {
        return app(\App\Managers\TenantManager::class)->current();
    }
}

if (!function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        return app(\App\Managers\TenantManager::class)->currentId();
    }
}

if (!function_exists('tenant_route')) {
    function tenant_route(string $name, array $parameters = []): string
    {
        $businessId = tenant_id();

        if ($businessId) {
            $parameters['business'] = $businessId;
        }

        return route($name, $parameters);
    }
}
