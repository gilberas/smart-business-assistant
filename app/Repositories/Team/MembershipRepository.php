<?php

namespace App\Repositories\Team;

use App\Models\Business;
use App\Models\MembershipActivity;
use App\Models\User;

class MembershipRepository
{
    public function addMember(Business $business, User $user, string $role): void
    {
        $business->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'status' => 'active',
                'joined_at' => now(),
                'last_switched_at' => now(),
            ],
        ]);
    }

    public function removeMember(Business $business, User $user): void
    {
        $business->users()->detach($user->id);
    }

    public function updateRole(Business $business, User $user, string $role): void
    {
        $business->users()->updateExistingPivot($user->id, ['role' => $role]);
    }

    public function updateStatus(Business $business, User $user, string $status): void
    {
        $business->users()->updateExistingPivot($user->id, ['status' => $status]);
    }

    public function members(Business $business): iterable
    {
        return $business->users()
            ->withPivot(['role', 'status', 'joined_at', 'last_switched_at'])
            ->orderBy('business_user.role')
            ->orderBy('users.name')
            ->get();
    }

    public function activity(Business $business, int $limit = 50): iterable
    {
        return MembershipActivity::with(['user', 'actor'])
            ->where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
