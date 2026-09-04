<?php

namespace App\Services\Team;

use App\Enums\InvitationStatus;
use App\Events\Team\InvitationAccepted;
use App\Events\Team\InvitationCancelled;
use App\Events\Team\InvitationSent;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\User;
use App\Notifications\Team\InvitationEmail;
use App\Repositories\Team\InvitationRepository;
use App\Support\RoleRegistry;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function __construct(
        private readonly InvitationRepository $invitationRepository,
        private readonly MembershipService $membershipService,
    ) {}

    public function invite(User $inviter, Business $business, string $email, string $role, ?string $message = null): BusinessInvitation
    {
        $tenantRoles = RoleRegistry::tenantRoles();

        if (!in_array($role, $tenantRoles, true)) {
            throw ValidationException::withMessages([
                'role' => [__('team.invalid_role')],
            ]);
        }

        if ($business->users()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => [__('team.user_already_member')],
            ]);
        }

        $pending = $this->invitationRepository->findPending($business->id, $email);

        if ($pending) {
            throw ValidationException::withMessages([
                'email' => [__('team.pending_invitation_exists')],
            ]);
        }

        $token = Str::random(64);
        $expiresAt = now()->addDays(7);

        $invitation = $this->invitationRepository->create([
            'business_id' => $business->id,
            'invited_by' => $inviter->id,
            'email' => $email,
            'role' => $role,
            'token' => $token,
            'status' => InvitationStatus::Pending->value,
            'message' => $message,
            'expires_at' => $expiresAt,
        ]);

        $user = User::where('email', $email)->first();

        if ($user) {
            $invitation->user()->associate($user);
            $invitation->save();
        }

        event(new InvitationSent($invitation));

        if ($user) {
            Notification::send($user, new InvitationEmail($invitation));
        } else {
            Notification::route('mail', $email)->notify(new InvitationEmail($invitation));
        }

        return $invitation->fresh();
    }

    public function accept(string $token, ?User $user = null): BusinessInvitation
    {
        $invitation = $this->invitationRepository->findByToken($token);

        if (!$invitation || !$invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => [__('team.invalid_invitation')],
            ]);
        }

        if ($invitation->isExpired()) {
            $invitation->markAsExpired();
            throw ValidationException::withMessages([
                'token' => [__('team.invitation_expired')],
            ]);
        }

        $acceptedUser = $user;
        if (!$acceptedUser) {
            $acceptedUser = User::where('email', $invitation->email)->first();
        }

        if (!$acceptedUser) {
            throw ValidationException::withMessages([
                'email' => [__('team.invitation_requires_account')],
            ]);
        }

        if ($acceptedUser->email !== $invitation->email) {
            throw ValidationException::withMessages([
                'token' => [__('team.invalid_invitation')],
            ]);
        }

        $this->membershipService->addMember(
            $invitation->business,
            $acceptedUser,
            $invitation->role,
        );

        $invitation->markAsAccepted();

        event(new InvitationAccepted($invitation));

        return $invitation->fresh();
    }

    public function cancel(BusinessInvitation $invitation, User $cancelledBy): void
    {
        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => [__('team.invitation_not_pending')],
            ]);
        }

        $invitation->markAsCancelled();

        event(new InvitationCancelled($invitation));
    }

    public function resend(BusinessInvitation $invitation): BusinessInvitation
    {
        if (!$invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => [__('team.invitation_not_pending')],
            ]);
        }

        if ($invitation->isExpired()) {
            $invitation->update([
                'token' => Str::random(64),
                'expires_at' => now()->addDays(7),
            ]);
        }

        $invitation->refresh();

        $user = User::where('email', $invitation->email)->first();

        if ($user) {
            Notification::send($user, new InvitationEmail($invitation));
        } else {
            Notification::route('mail', $invitation->email)->notify(new InvitationEmail($invitation));
        }

        return $invitation->fresh();
    }

    public function pending(Business $business): iterable
    {
        return $this->invitationRepository->pendingForBusiness($business);
    }

    public function findByToken(string $token): ?BusinessInvitation
    {
        return $this->invitationRepository->findByToken($token);
    }

    public function markExpired(): int
    {
        return $this->invitationRepository->markExpired();
    }
}
