<?php

namespace App\Services;

use App\Managers\TenantManager;
use App\Models\Business;
use App\Models\User;

class TenantService
{
    public function __construct(
        private readonly TenantManager $tenantManager,
    ) {}

    public function current(): ?Business
    {
        return $this->tenantManager->current();
    }

    public function currentOrFail(): Business
    {
        $business = $this->tenantManager->current();

        if (!$business) {
            abort(400, 'No business selected.');
        }

        return $business;
    }

    public function currentId(): ?int
    {
        return $this->tenantManager->currentId();
    }

    public function switch(User $user, Business $business): bool
    {
        return $this->tenantManager->switch($user, $business);
    }

    public function isCurrent(Business $business): bool
    {
        return $this->tenantManager->currentId() === $business->id;
    }

    public function available(User $user): iterable
    {
        if ($user->hasRole(\App\Support\RoleRegistry::SUPER_ADMIN)) {
            return \App\Models\Business::orderBy('name')->get();
        }

        return $user->businesses()->wherePivot('status', 'active')->get();
    }

    /**
     * Resolve a business by id for the given user.
     *
     * Super admins can resolve any business on the platform; regular users are
     * restricted to businesses they are a member of.
     */
    public function findBusiness(User $user, int $id): \App\Models\Business
    {
        if ($user->hasRole(\App\Support\RoleRegistry::SUPER_ADMIN)) {
            return \App\Models\Business::findOrFail($id);
        }

        return $user->businesses()->findOrFail($id);
    }

    public function memberIn(Business $business, User $user): bool
    {
        return $business->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function memberRole(Business $business, User $user): ?string
    {
        $pivot = $business->users()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot;

        return $pivot?->role;
    }

    public function isOwner(Business $business, User $user): bool
    {
        if ($user->hasRole(\App\Support\RoleRegistry::SUPER_ADMIN)) {
            return true;
        }

        return $business->owner_id === $user->id;
    }

    public function isAdminOrAbove(Business $business, User $user): bool
    {
        if ($user->hasRole(\App\Support\RoleRegistry::SUPER_ADMIN)) {
            return true;
        }

        $role = $this->memberRole($business, $user);

        return $role && in_array($role, [\App\Support\RoleRegistry::BUSINESS_OWNER, \App\Support\RoleRegistry::BUSINESS_ADMIN]);
    }
}
