<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PermissionRegistry;

class WhatsAppPolicy
{
    public function view(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::WHATSAPP_VIEW);
    }

    public function send(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::WHATSAPP_SEND);
    }

    public function manageTemplates(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::WHATSAPP_TEMPLATES_MANAGE);
    }

    public function managePhone(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::WHATSAPP_PHONE_MANAGE);
    }

    public function manageWebhooks(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::WHATSAPP_WEBHOOKS_MANAGE);
    }

    public function connect(User $user): bool
    {
        return $user->canInBusiness(PermissionRegistry::WHATSAPP_WEBHOOKS_MANAGE) ||
            $user->canInBusiness(PermissionRegistry::BUSINESS_SETTINGS_MANAGE);
    }

    public function disconnect(User $user): bool
    {
        return $this->connect($user);
    }
}
