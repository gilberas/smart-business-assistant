<?php

namespace App\Managers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Cache;

class TenantManager
{
    private ?Business $currentBusiness = null;

    private bool $resolved = false;

    public function __construct(
        private readonly SessionManager $session,
    ) {}

    public function resolve(User $user): ?Business
    {
        if ($this->resolved) {
            return $this->currentBusiness;
        }

        $this->resolved = true;

        $businessId = $this->session->get('current_business_id')
            ?? Cache::get("user.{$user->id}.current_business");

        if ($businessId && $user->businesses()->where('business_id', $businessId)->exists()) {
            $this->currentBusiness = Business::find($businessId);
        }

        if (!$this->currentBusiness) {
            $this->currentBusiness = $user->businesses()->first();
        }

        // Super admins are not attached to any specific business. Fall back to
        // the first business in the platform so they always have a tenant context
        // to operate within (they can switch via the business switcher).
        if (!$this->currentBusiness && $user->hasRole(\App\Support\RoleRegistry::SUPER_ADMIN)) {
            $this->currentBusiness = \App\Models\Business::orderBy('created_at')->first();
        }

        if ($this->currentBusiness) {
            $this->store($user, $this->currentBusiness->id);
        }

        return $this->currentBusiness;
    }

    public function switch(User $user, Business $business): bool
    {
        if (!$user->hasRole(\App\Support\RoleRegistry::SUPER_ADMIN)
            && !$user->businesses()->where('business_id', $business->id)->exists()) {
            return false;
        }

        $this->currentBusiness = $business;
        $this->resolved = true;

        $this->store($user, $business->id);

        $user->businesses()->updateExistingPivot($business->id, [
            'last_switched_at' => now(),
        ]);

        return true;
    }

    public function current(): ?Business
    {
        return $this->currentBusiness;
    }

    public function currentId(): ?int
    {
        return $this->currentBusiness?->id;
    }

    public function clear(): void
    {
        $this->currentBusiness = null;
        $this->resolved = false;
    }

    private function store(User $user, int $businessId): void
    {
        $this->session->put('current_business_id', $businessId);
        Cache::put("user.{$user->id}.current_business", $businessId, now()->addDay());
    }
}
