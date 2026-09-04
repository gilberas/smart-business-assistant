<?php

namespace App\Services;

use App\Managers\TenantManager;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Repositories\BusinessRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BusinessService
{
    public function __construct(
        private readonly BusinessRepository $businessRepository,
        private readonly TenantManager $tenantManager,
        private readonly TenantService $tenantService,
    ) {}

    public function create(User $user, array $data): Business
    {
        if (!isset($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $business = $this->businessRepository->create($user, $data);

        try {
            $this->initializeSettings($business);
        } catch (\Throwable $e) {
            Log::error('Failed to initialize business settings', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
            throw ValidationException::withMessages([
                'business' => ['Business created but settings initialization failed. Please try again.'],
            ]);
        }

        try {
            $this->initializeBranding($business);
        } catch (\Throwable $e) {
            Log::error('Failed to initialize business branding', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }

        try {
            $this->tenantManager->switch($user, $business);
        } catch (\Throwable $e) {
            Log::error('Failed to switch tenant after business creation', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
            report($e);
        }

        return $business;
    }

    public function update(User $user, Business $business, array $data): Business
    {
        if (!$this->tenantService->isAdminOrAbove($business, $user)) {
            throw ValidationException::withMessages([
                'business' => [__('tenant.unauthorized')],
            ]);
        }

        if (isset($data['slug']) && $data['slug'] !== $business->slug) {
            $data['slug'] = $this->generateUniqueSlug($data['slug']);
        }

        return $this->businessRepository->update($business, $data);
    }

    public function delete(User $user, Business $business): void
    {
        if (!$this->tenantService->isOwner($business, $user)) {
            throw ValidationException::withMessages([
                'business' => [__('tenant.only_owner_can_delete')],
            ]);
        }

        $this->businessRepository->delete($business);
    }

    public function list(User $user, int $perPage = 15): iterable
    {
        if ($user->hasRole(\App\Support\RoleRegistry::SUPER_ADMIN)) {
            return \App\Models\Business::orderBy('name')->paginate($perPage);
        }

        return $this->businessRepository->paginate($user, $perPage);
    }

    public function members(Business $business): iterable
    {
        return $this->businessRepository->members($business);
    }

    public function addMember(User $user, Business $business, User $member, string $role = 'agent'): void
    {
        if (!$this->tenantService->isAdminOrAbove($business, $user)) {
            throw ValidationException::withMessages([
                'business' => [__('tenant.unauthorized')],
            ]);
        }

        $this->businessRepository->addMember($business, $member, $role);
    }

    public function updateMemberRole(User $user, Business $business, User $member, string $role): void
    {
        if (!$this->tenantService->isAdminOrAbove($business, $user)) {
            throw ValidationException::withMessages([
                'business' => [__('tenant.unauthorized')],
            ]);
        }

        if ($member->id === $business->owner_id) {
            throw ValidationException::withMessages([
                'member' => [__('tenant.cannot_change_owner_role')],
            ]);
        }

        $this->businessRepository->updateMemberRole($business, $member, $role);
    }

    public function removeMember(User $user, Business $business, User $member): void
    {
        if (!$this->tenantService->isAdminOrAbove($business, $user)) {
            throw ValidationException::withMessages([
                'business' => [__('tenant.unauthorized')],
            ]);
        }

        if ($member->id === $business->owner_id) {
            throw ValidationException::withMessages([
                'member' => [__('tenant.cannot_remove_owner')],
            ]);
        }

        $this->businessRepository->removeMember($business, $member);
    }

    public function getSettings(Business $business): BusinessSetting
    {
        return $business->settings ?? $this->initializeSettings($business);
    }

    public function updateSettings(User $user, Business $business, array $data): BusinessSetting
    {
        if (!$this->tenantService->isAdminOrAbove($business, $user)) {
            throw ValidationException::withMessages([
                'business' => [__('tenant.unauthorized')],
            ]);
        }

        $settings = $this->getSettings($business);
        $settings->update($data);

        return $settings->fresh();
    }

    private function initializeSettings(Business $business): BusinessSetting
    {
        return $business->settings()->create([
            'business_hours' => [
                'monday' => ['09:00-18:00'],
                'tuesday' => ['09:00-18:00'],
                'wednesday' => ['09:00-18:00'],
                'thursday' => ['09:00-18:00'],
                'friday' => ['09:00-18:00'],
                'saturday' => null,
                'sunday' => null,
            ],
            'ai_provider' => 'openai',
            'ai_model' => 'gpt-4o',
            'ai_temperature' => 0.7,
            'ai_max_tokens' => 2048,
            'settings' => [
                'auto_reply' => true,
                'office_hours_only' => false,
                'language' => $business->locale,
                'timezone' => $business->timezone,
                'currency' => $business->currency,
            ],
        ]);
    }

    private function initializeBranding(Business $business): void
    {
        $business->update([
            'branding' => [
                'primary_color' => '#2563eb',
                'secondary_color' => '#7c3aed',
                'accent_color' => '#06b6d4',
                'font_family' => 'Inter, system-ui, sans-serif',
                'logo_url' => null,
                'favicon_url' => null,
            ],
        ]);
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $counter = 1;

        while (Business::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
