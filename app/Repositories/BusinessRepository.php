<?php

namespace App\Repositories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class BusinessRepository
{
    public function paginate(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->businesses()
            ->withPivot(['role', 'status', 'joined_at'])
            ->orderBy('business_user.last_switched_at', 'desc')
            ->orderBy('businesses.name')
            ->paginate($perPage);
    }

    public function create(User $owner, array $data): Business
    {
        $business = Business::create([
            'uuid' => (string) Str::uuid(),
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'website' => $data['website'] ?? null,
            'timezone' => $data['timezone'] ?? $owner->timezone ?? 'UTC',
            'locale' => $data['locale'] ?? $owner->locale ?? 'en',
            'currency' => $data['currency'] ?? 'USD',
            'industry' => $data['industry'] ?? null,
            'employee_count' => $data['employee_count'] ?? null,
            'onboarding_step' => $data['onboarding_step'] ?? null,
            'owner_name' => $data['owner_name'] ?? $owner->name,
            'owner_email' => $data['owner_email'] ?? $owner->email,
            'owner_phone' => $data['owner_phone'] ?? $owner->phone,
            'status' => 'active',
        ]);

        $business->users()->attach($owner->id, [
            'role' => \App\Support\RoleRegistry::BUSINESS_OWNER,
            'status' => 'active',
            'joined_at' => now(),
            'last_switched_at' => now(),
        ]);

        return $business;
    }

    public function update(Business $business, array $data): Business
    {
        $business->update($data);

        return $business->fresh();
    }

    public function delete(Business $business): void
    {
        $business->delete();
    }

    public function restore(Business $business): void
    {
        $business->restore();
    }

    public function findById(int $id): ?Business
    {
        return Business::find($id);
    }

    public function findBySlug(string $slug): ?Business
    {
        return Business::where('slug', $slug)->first();
    }

    public function findByUuid(string $uuid): ?Business
    {
        return Business::where('uuid', $uuid)->first();
    }

    public function members(Business $business): iterable
    {
        return $business->users()
            ->withPivot(['role', 'status', 'joined_at', 'last_switched_at'])
            ->orderBy('business_user.role')
            ->orderBy('users.name')
            ->get();
    }

    public function addMember(Business $business, User $user, string $role = 'agent'): void
    {
        $business->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'status' => 'active',
                'joined_at' => now(),
            ],
        ]);
    }

    public function updateMemberRole(Business $business, User $user, string $role): void
    {
        $business->users()->updateExistingPivot($user->id, [
            'role' => $role,
        ]);
    }

    public function removeMember(Business $business, User $user): void
    {
        $business->users()->detach($user->id);
    }

    public function suspendMember(Business $business, User $user): void
    {
        $business->users()->updateExistingPivot($user->id, [
            'status' => 'suspended',
        ]);
    }

    public function activateMember(Business $business, User $user): void
    {
        $business->users()->updateExistingPivot($user->id, [
            'status' => 'active',
        ]);
    }
}
