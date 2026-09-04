<?php

namespace App\Services\Team;

use App\Contracts\AuthorizationServiceInterface;
use App\Enums\MembershipActivityType;
use App\Enums\MembershipStatus;
use App\Events\Team\MemberAdded;
use App\Events\Team\MemberRemoved;
use App\Events\Team\MemberRestored;
use App\Events\Team\MemberSuspended;
use App\Events\Team\OwnershipTransferred;
use App\Events\Team\RoleChanged;
use App\Models\Business;
use App\Models\MembershipActivity;
use App\Models\User;
use App\Notifications\Team\MemberRemoved as MemberRemovedNotification;
use App\Notifications\Team\OwnershipTransferred as OwnershipTransferredNotification;
use App\Notifications\Team\RoleUpdated;
use App\Repositories\Team\MembershipRepository;
use App\Support\RoleRegistry;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class MembershipService
{
    public function __construct(
        private readonly MembershipRepository $membershipRepository,
        private readonly AuthorizationServiceInterface $authorizationService,
    ) {}

    public function addMember(Business $business, User $user, string $role = 'agent'): void
    {
        $this->membershipRepository->addMember($business, $user, $role);

        $this->authorizationService->flushPermissionCache($user, $business);

        event(new MemberAdded($business, $user, $user, $role));

        MembershipActivity::record(
            $business->id,
            $user->id,
            $user->id,
            MembershipActivityType::Joined,
            ['role' => $role],
        );
    }

    public function removeMember(Business $business, User $member, User $removedBy): void
    {
        if ($member->id === $business->owner_id) {
            throw ValidationException::withMessages([
                'member' => [__('team.cannot_remove_owner')],
            ]);
        }

        if ($member->id === $removedBy->id) {
            throw ValidationException::withMessages([
                'member' => [__('team.use_leave_instead')],
            ]);
        }

        $this->membershipRepository->removeMember($business, $member);

        $this->authorizationService->flushPermissionCache($member, $business);

        event(new MemberRemoved($business, $member, $removedBy));

        MembershipActivity::record(
            $business->id,
            $member->id,
            $removedBy->id,
            MembershipActivityType::Removed,
        );

        Notification::send($member, new MemberRemovedNotification($business, $removedBy));
    }

    public function leaveBusiness(Business $business, User $user): void
    {
        if ($user->id === $business->owner_id) {
            throw ValidationException::withMessages([
                'member' => [__('team.owner_cannot_leave')],
            ]);
        }

        $this->membershipRepository->removeMember($business, $user);

        $this->authorizationService->flushPermissionCache($user, $business);

        MembershipActivity::record(
            $business->id,
            $user->id,
            $user->id,
            MembershipActivityType::Left,
        );
    }

    public function changeRole(Business $business, User $member, User $changedBy, string $newRole): void
    {
        if ($member->id === $business->owner_id && $newRole !== RoleRegistry::BUSINESS_OWNER) {
            throw ValidationException::withMessages([
                'role' => [__('team.cannot_change_owner_role')],
            ]);
        }

        $tenantRoles = RoleRegistry::tenantRoles();

        if (!in_array($newRole, $tenantRoles, true)) {
            throw ValidationException::withMessages([
                'role' => [__('team.invalid_role')],
            ]);
        }

        $oldRole = $business->users()
            ->where('user_id', $member->id)
            ->first()
            ?->pivot
            ?->role;

        if (!$oldRole) {
            throw ValidationException::withMessages([
                'member' => [__('team.member_not_found')],
            ]);
        }

        $this->membershipRepository->updateRole($business, $member, $newRole);

        $this->authorizationService->flushPermissionCache($member, $business);

        event(new RoleChanged($business, $member, $changedBy, $oldRole, $newRole));

        MembershipActivity::record(
            $business->id,
            $member->id,
            $changedBy->id,
            MembershipActivityType::RoleChanged,
            ['old_role' => $oldRole, 'new_role' => $newRole],
        );

        Notification::send($member, new RoleUpdated($business, $changedBy, $oldRole, $newRole));
    }

    public function suspend(Business $business, User $member, User $suspendedBy): void
    {
        if ($member->id === $business->owner_id) {
            throw ValidationException::withMessages([
                'member' => [__('team.cannot_suspend_owner')],
            ]);
        }

        $this->membershipRepository->updateStatus($business, $member, MembershipStatus::Suspended->value);

        $this->authorizationService->flushPermissionCache($member, $business);

        event(new MemberSuspended($business, $member, $suspendedBy));

        MembershipActivity::record(
            $business->id,
            $member->id,
            $suspendedBy->id,
            MembershipActivityType::Suspended,
        );
    }

    public function restore(Business $business, User $member, User $restoredBy): void
    {
        $this->membershipRepository->updateStatus($business, $member, MembershipStatus::Active->value);

        $this->authorizationService->flushPermissionCache($member, $business);

        event(new MemberRestored($business, $member, $restoredBy));

        MembershipActivity::record(
            $business->id,
            $member->id,
            $restoredBy->id,
            MembershipActivityType::Restored,
        );
    }

    public function transferOwnership(Business $business, User $newOwner, User $transferredBy): void
    {
        $previousOwner = $business->owner;

        if ($newOwner->id === $previousOwner->id) {
            throw ValidationException::withMessages([
                'user_id' => [__('team.already_owner')],
            ]);
        }

        if (!$business->users()->where('user_id', $newOwner->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => [__('team.new_owner_not_member')],
            ]);
        }

        $business->owner_id = $newOwner->id;
        $business->save();

        $this->membershipRepository->updateRole($business, $previousOwner, RoleRegistry::BUSINESS_ADMIN);
        $this->membershipRepository->updateRole($business, $newOwner, RoleRegistry::BUSINESS_OWNER);

        $this->authorizationService->flushPermissionCache($previousOwner, $business);
        $this->authorizationService->flushPermissionCache($newOwner, $business);

        event(new OwnershipTransferred($business, $previousOwner, $newOwner, $transferredBy));

        MembershipActivity::record(
            $business->id,
            $previousOwner->id,
            $transferredBy->id,
            MembershipActivityType::OwnershipTransferred,
            ['new_owner_id' => $newOwner->id, 'new_owner_name' => $newOwner->name],
        );

        MembershipActivity::record(
            $business->id,
            $newOwner->id,
            $transferredBy->id,
            MembershipActivityType::OwnershipTransferred,
            ['previous_owner_id' => $previousOwner->id, 'previous_owner_name' => $previousOwner->name],
        );

        Notification::send($previousOwner, new OwnershipTransferredNotification($business, $previousOwner, $newOwner));
        Notification::send($newOwner, new OwnershipTransferredNotification($business, $previousOwner, $newOwner));
    }

    public function activity(Business $business, int $limit = 50): iterable
    {
        return $this->membershipRepository->activity($business, $limit);
    }

    public function members(Business $business): iterable
    {
        return $this->membershipRepository->members($business);
    }
}
