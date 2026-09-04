<?php

namespace App\Policies;

use App\Enums\Conversation\ConversationStatus;
use App\Models\Conversation;
use App\Models\User;
use App\Support\PermissionRegistry;

class ConversationPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_VIEW);
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_VIEW)
            && $user->businesses()->where('business_id', $conversation->business_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_CREATE);
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_UPDATE)
            && $user->businesses()->where('business_id', $conversation->business_id)->exists();
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_DELETE)
            && $user->businesses()->where('business_id', $conversation->business_id)->exists();
    }

    public function assign(User $user, Conversation $conversation): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_ASSIGN)
            && $user->businesses()->where('business_id', $conversation->business_id)->exists();
    }

    public function close(User $user, Conversation $conversation): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_CLOSE)
            && $conversation->status instanceof ConversationStatus && $conversation->status->isActive()
            && $user->businesses()->where('business_id', $conversation->business_id)->exists();
    }

    public function reopen(User $user, Conversation $conversation): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_REOPEN)
            && !$conversation->isActive()
            && $user->businesses()->where('business_id', $conversation->business_id)->exists();
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_REPLY)
            && $user->businesses()->where('business_id', $conversation->business_id)->exists();
    }

    public function viewMessages(User $user, Conversation $conversation): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_VIEW)
            && $user->businesses()->where('business_id', $conversation->business_id)->exists();
    }

    public function manageLabels(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::CONVERSATIONS_MANAGE_LABELS);
    }
}
