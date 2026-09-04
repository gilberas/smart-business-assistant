<?php

namespace App\Repositories\Team;

use App\Enums\InvitationStatus;
use App\Models\Business;
use App\Models\BusinessInvitation;

class InvitationRepository
{
    public function create(array $data): BusinessInvitation
    {
        return BusinessInvitation::create($data);
    }

    public function findById(int $id): ?BusinessInvitation
    {
        return BusinessInvitation::with(['business', 'inviter', 'user'])->find($id);
    }

    public function findByToken(string $token): ?BusinessInvitation
    {
        return BusinessInvitation::with(['business', 'inviter', 'user'])
            ->where('token', $token)
            ->first();
    }

    public function findPending(int $businessId, string $email): ?BusinessInvitation
    {
        return BusinessInvitation::where('business_id', $businessId)
            ->where('email', $email)
            ->where('status', InvitationStatus::Pending->value)
            ->first();
    }

    public function pendingForBusiness(Business $business): iterable
    {
        return BusinessInvitation::with(['inviter', 'user'])
            ->where('business_id', $business->id)
            ->where('status', InvitationStatus::Pending->value)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function forBusiness(Business $business): iterable
    {
        return BusinessInvitation::with(['inviter', 'user'])
            ->where('business_id', $business->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function markExpired(): int
    {
        return BusinessInvitation::where('status', InvitationStatus::Pending->value)
            ->where('expires_at', '<', now())
            ->update(['status' => InvitationStatus::Expired->value]);
    }
}
