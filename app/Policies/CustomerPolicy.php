<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PermissionRegistry;

class CustomerPolicy
{
    public function view(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_CREATE);
    }

    public function update(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_UPDATE);
    }

    public function delete(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_DELETE);
    }

    public function archive(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_UPDATE);
    }

    public function restore(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_UPDATE);
    }

    public function merge(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_UPDATE);
    }

    public function export(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_VIEW);
    }

    public function import(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CUSTOMERS_CREATE);
    }
}
