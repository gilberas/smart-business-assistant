<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
use App\Support\PermissionRegistry;

class WorkflowPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::WORKFLOWS_VIEW);
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->canInBusiness(PermissionRegistry::WORKFLOWS_VIEW)
            && $user->businesses()->where('business_id', $workflow->business_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::WORKFLOWS_CREATE);
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->canInBusiness(PermissionRegistry::WORKFLOWS_UPDATE)
            && $user->businesses()->where('business_id', $workflow->business_id)->exists();
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->canInBusiness(PermissionRegistry::WORKFLOWS_DELETE)
            && $user->businesses()->where('business_id', $workflow->business_id)->exists();
    }

    public function execute(User $user, Workflow $workflow): bool
    {
        return $user->canInBusiness(PermissionRegistry::WORKFLOWS_EXECUTE)
            && $user->businesses()->where('business_id', $workflow->business_id)->exists();
    }
}