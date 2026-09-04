<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\User;
use App\Services\TenantService;

class BusinessPolicy
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    public function view(User $user, Business $business): bool
    {
        return $this->tenantService->memberIn($business, $user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Business $business): bool
    {
        return $this->tenantService->isAdminOrAbove($business, $user);
    }

    public function delete(User $user, Business $business): bool
    {
        return $this->tenantService->isOwner($business, $user);
    }

    public function manageMembers(User $user, Business $business): bool
    {
        return $this->tenantService->isAdminOrAbove($business, $user);
    }

    public function manageSettings(User $user, Business $business): bool
    {
        return $this->tenantService->isAdminOrAbove($business, $user);
    }
}
