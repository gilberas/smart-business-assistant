<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PermissionRegistry;

class KnowledgePolicy
{
    public function view(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_CREATE);
    }

    public function update(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_UPDATE);
    }

    public function delete(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_DELETE);
    }

    public function manageCategories(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_UPDATE);
    }

    public function manageTags(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_UPDATE);
    }

    public function manageFaqs(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_UPDATE);
    }

    public function manageCollections(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_UPDATE);
    }

    public function manageBusinessInfo(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_UPDATE);
    }

    public function uploadMedia(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::KNOWLEDGE_CREATE);
    }
}