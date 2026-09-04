<?php

namespace App\Support;

final class RoleRegistry
{
    const SUPER_ADMIN = 'super-admin';
    const BUSINESS_OWNER = 'business-owner';
    const BUSINESS_ADMIN = 'business-admin';
    const MANAGER = 'manager';
    const AGENT = 'agent';
    const VIEWER = 'viewer';

    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::BUSINESS_OWNER,
            self::BUSINESS_ADMIN,
            self::MANAGER,
            self::AGENT,
            self::VIEWER,
        ];
    }

    public static function tenantRoles(): array
    {
        return [
            self::BUSINESS_OWNER,
            self::BUSINESS_ADMIN,
            self::MANAGER,
            self::AGENT,
            self::VIEWER,
        ];
    }

    public static function hierarchy(): array
    {
        return [
            self::SUPER_ADMIN => 100,
            self::BUSINESS_OWNER => 80,
            self::BUSINESS_ADMIN => 60,
            self::MANAGER => 40,
            self::AGENT => 20,
            self::VIEWER => 10,
        ];
    }

    public static function rank(string $role): int
    {
        return self::hierarchy()[$role] ?? 0;
    }

    public static function isHigherThan(string $role, string $compareTo): bool
    {
        return self::rank($role) > self::rank($compareTo);
    }

    public static function isLowerThan(string $role, string $compareTo): bool
    {
        return self::rank($role) < self::rank($compareTo);
    }

    public static function canManage(string $actorRole, string $targetRole): bool
    {
        return self::rank($actorRole) > self::rank($targetRole);
    }

    public static function label(string $role): string
    {
        $labels = [
            self::SUPER_ADMIN => 'Super Admin',
            self::BUSINESS_OWNER => 'Business Owner',
            self::BUSINESS_ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::AGENT => 'Agent',
            self::VIEWER => 'Viewer',
        ];

        return $labels[$role] ?? $role;
    }

    public static function description(string $role): string
    {
        $descriptions = [
            self::SUPER_ADMIN => 'Global platform administrator with unrestricted access.',
            self::BUSINESS_OWNER => 'Full business ownership including deletion and billing.',
            self::BUSINESS_ADMIN => 'Complete business management except deletion.',
            self::MANAGER => 'Operational management with limited administrative access.',
            self::AGENT => 'Daily operations including customer interaction.',
            self::VIEWER => 'Read-only access to business data.',
        ];

        return $descriptions[$role] ?? '';
    }

    public static function defaultPermissions(string $role): array
    {
        return match ($role) {
            self::SUPER_ADMIN => PermissionRegistry::all(),

            self::BUSINESS_OWNER => [
                PermissionRegistry::BUSINESS_VIEW,
                PermissionRegistry::BUSINESS_CREATE,
                PermissionRegistry::BUSINESS_UPDATE,
                PermissionRegistry::BUSINESS_DELETE,
                PermissionRegistry::BUSINESS_SETTINGS_MANAGE,

                PermissionRegistry::TEAM_VIEW,
                PermissionRegistry::TEAM_INVITE,
                PermissionRegistry::TEAM_ROLE_UPDATE,
                PermissionRegistry::TEAM_REMOVE,

                PermissionRegistry::CUSTOMERS_VIEW,
                PermissionRegistry::CUSTOMERS_CREATE,
                PermissionRegistry::CUSTOMERS_UPDATE,
                PermissionRegistry::CUSTOMERS_DELETE,
                PermissionRegistry::CUSTOMERS_EXPORT,
                PermissionRegistry::CUSTOMERS_IMPORT,
                PermissionRegistry::CUSTOMERS_MERGE,

                PermissionRegistry::PRODUCTS_VIEW,
                PermissionRegistry::PRODUCTS_CREATE,
                PermissionRegistry::PRODUCTS_UPDATE,
                PermissionRegistry::PRODUCTS_DELETE,

                PermissionRegistry::CATEGORIES_VIEW,
                PermissionRegistry::CATEGORIES_CREATE,
                PermissionRegistry::CATEGORIES_UPDATE,
                PermissionRegistry::CATEGORIES_DELETE,

                PermissionRegistry::KNOWLEDGE_VIEW,
                PermissionRegistry::KNOWLEDGE_CREATE,
                PermissionRegistry::KNOWLEDGE_UPDATE,
                PermissionRegistry::KNOWLEDGE_DELETE,

                PermissionRegistry::WHATSAPP_VIEW,
                PermissionRegistry::WHATSAPP_SEND,
                PermissionRegistry::WHATSAPP_TEMPLATES_MANAGE,
                PermissionRegistry::WHATSAPP_PHONE_MANAGE,
                PermissionRegistry::WHATSAPP_WEBHOOKS_MANAGE,

                PermissionRegistry::CONVERSATIONS_VIEW,
                PermissionRegistry::CONVERSATIONS_CREATE,
                PermissionRegistry::CONVERSATIONS_UPDATE,
                PermissionRegistry::CONVERSATIONS_REPLY,
                PermissionRegistry::CONVERSATIONS_ASSIGN,
                PermissionRegistry::CONVERSATIONS_CLOSE,
                PermissionRegistry::CONVERSATIONS_REOPEN,
                PermissionRegistry::CONVERSATIONS_DELETE,
                PermissionRegistry::CONVERSATIONS_MANAGE_LABELS,

                PermissionRegistry::AI_CHAT,
                PermissionRegistry::AI_TRAIN,
                PermissionRegistry::AI_CONFIGURE,
                PermissionRegistry::AI_VIEW_USAGE,
                PermissionRegistry::AI_MANAGE_PROVIDERS,
                PermissionRegistry::AI_MANAGE_PROMPTS,

                PermissionRegistry::ANALYTICS_VIEW,
                PermissionRegistry::ANALYTICS_EXPORT,

                PermissionRegistry::BROADCASTS_VIEW,
                PermissionRegistry::BROADCASTS_CREATE,
                PermissionRegistry::BROADCASTS_SEND,
                PermissionRegistry::BROADCASTS_DELETE,

                PermissionRegistry::NOTIFICATIONS_VIEW,
                PermissionRegistry::NOTIFICATIONS_MANAGE,

                PermissionRegistry::SETTINGS_VIEW,
                PermissionRegistry::SETTINGS_MANAGE,

                PermissionRegistry::AUDIT_VIEW,
                PermissionRegistry::AUDIT_EXPORT,
            ],

            self::BUSINESS_ADMIN => [
                PermissionRegistry::BUSINESS_VIEW,
                PermissionRegistry::BUSINESS_UPDATE,
                PermissionRegistry::BUSINESS_SETTINGS_MANAGE,

                PermissionRegistry::TEAM_VIEW,
                PermissionRegistry::TEAM_INVITE,
                PermissionRegistry::TEAM_ROLE_UPDATE,
                PermissionRegistry::TEAM_REMOVE,

                PermissionRegistry::CUSTOMERS_VIEW,
                PermissionRegistry::CUSTOMERS_CREATE,
                PermissionRegistry::CUSTOMERS_UPDATE,
                PermissionRegistry::CUSTOMERS_DELETE,
                PermissionRegistry::CUSTOMERS_EXPORT,
                PermissionRegistry::CUSTOMERS_IMPORT,
                PermissionRegistry::CUSTOMERS_MERGE,

                PermissionRegistry::PRODUCTS_VIEW,
                PermissionRegistry::PRODUCTS_CREATE,
                PermissionRegistry::PRODUCTS_UPDATE,
                PermissionRegistry::PRODUCTS_DELETE,

                PermissionRegistry::CATEGORIES_VIEW,
                PermissionRegistry::CATEGORIES_CREATE,
                PermissionRegistry::CATEGORIES_UPDATE,
                PermissionRegistry::CATEGORIES_DELETE,

                PermissionRegistry::KNOWLEDGE_VIEW,
                PermissionRegistry::KNOWLEDGE_CREATE,
                PermissionRegistry::KNOWLEDGE_UPDATE,
                PermissionRegistry::KNOWLEDGE_DELETE,

                PermissionRegistry::WHATSAPP_VIEW,
                PermissionRegistry::WHATSAPP_SEND,
                PermissionRegistry::WHATSAPP_TEMPLATES_MANAGE,
                PermissionRegistry::WHATSAPP_PHONE_MANAGE,
                PermissionRegistry::WHATSAPP_WEBHOOKS_MANAGE,

                PermissionRegistry::CONVERSATIONS_VIEW,
                PermissionRegistry::CONVERSATIONS_CREATE,
                PermissionRegistry::CONVERSATIONS_UPDATE,
                PermissionRegistry::CONVERSATIONS_REPLY,
                PermissionRegistry::CONVERSATIONS_ASSIGN,
                PermissionRegistry::CONVERSATIONS_CLOSE,
                PermissionRegistry::CONVERSATIONS_REOPEN,
                PermissionRegistry::CONVERSATIONS_DELETE,
                PermissionRegistry::CONVERSATIONS_MANAGE_LABELS,

                PermissionRegistry::AI_CHAT,
                PermissionRegistry::AI_TRAIN,
                PermissionRegistry::AI_CONFIGURE,
                PermissionRegistry::AI_VIEW_USAGE,
                PermissionRegistry::AI_MANAGE_PROMPTS,

                PermissionRegistry::ANALYTICS_VIEW,
                PermissionRegistry::ANALYTICS_EXPORT,

                PermissionRegistry::BROADCASTS_VIEW,
                PermissionRegistry::BROADCASTS_CREATE,
                PermissionRegistry::BROADCASTS_SEND,
                PermissionRegistry::BROADCASTS_DELETE,

                PermissionRegistry::NOTIFICATIONS_VIEW,
                PermissionRegistry::NOTIFICATIONS_MANAGE,

                PermissionRegistry::SETTINGS_VIEW,
                PermissionRegistry::SETTINGS_MANAGE,

                PermissionRegistry::AUDIT_VIEW,
                PermissionRegistry::AUDIT_EXPORT,
            ],

            self::MANAGER => [
                PermissionRegistry::BUSINESS_VIEW,

                PermissionRegistry::TEAM_VIEW,

                PermissionRegistry::CUSTOMERS_VIEW,
                PermissionRegistry::CUSTOMERS_CREATE,
                PermissionRegistry::CUSTOMERS_UPDATE,
                PermissionRegistry::CUSTOMERS_EXPORT,
                PermissionRegistry::CUSTOMERS_IMPORT,

                PermissionRegistry::PRODUCTS_VIEW,
                PermissionRegistry::PRODUCTS_CREATE,
                PermissionRegistry::PRODUCTS_UPDATE,

                PermissionRegistry::CATEGORIES_VIEW,
                PermissionRegistry::CATEGORIES_CREATE,
                PermissionRegistry::CATEGORIES_UPDATE,

                PermissionRegistry::KNOWLEDGE_VIEW,
                PermissionRegistry::KNOWLEDGE_CREATE,
                PermissionRegistry::KNOWLEDGE_UPDATE,

                PermissionRegistry::WHATSAPP_VIEW,
                PermissionRegistry::WHATSAPP_SEND,

                PermissionRegistry::CONVERSATIONS_VIEW,
                PermissionRegistry::CONVERSATIONS_CREATE,
                PermissionRegistry::CONVERSATIONS_UPDATE,
                PermissionRegistry::CONVERSATIONS_REPLY,
                PermissionRegistry::CONVERSATIONS_ASSIGN,
                PermissionRegistry::CONVERSATIONS_CLOSE,
                PermissionRegistry::CONVERSATIONS_REOPEN,
                PermissionRegistry::CONVERSATIONS_MANAGE_LABELS,

                PermissionRegistry::AI_CHAT,
                PermissionRegistry::AI_VIEW_USAGE,

                PermissionRegistry::ANALYTICS_VIEW,

                PermissionRegistry::BROADCASTS_VIEW,
                PermissionRegistry::BROADCASTS_CREATE,

                PermissionRegistry::NOTIFICATIONS_VIEW,

                PermissionRegistry::SETTINGS_VIEW,
            ],

            self::AGENT => [
                PermissionRegistry::BUSINESS_VIEW,

                PermissionRegistry::CUSTOMERS_VIEW,
                PermissionRegistry::CUSTOMERS_CREATE,
                PermissionRegistry::CUSTOMERS_UPDATE,

                PermissionRegistry::PRODUCTS_VIEW,

                PermissionRegistry::CATEGORIES_VIEW,

                PermissionRegistry::KNOWLEDGE_VIEW,

                PermissionRegistry::WHATSAPP_VIEW,
                PermissionRegistry::WHATSAPP_SEND,

                PermissionRegistry::CONVERSATIONS_VIEW,
                PermissionRegistry::CONVERSATIONS_REPLY,
                PermissionRegistry::CONVERSATIONS_CREATE,
                PermissionRegistry::CONVERSATIONS_MANAGE_LABELS,

                PermissionRegistry::AI_CHAT,

                PermissionRegistry::NOTIFICATIONS_VIEW,
            ],

            self::VIEWER => [
                PermissionRegistry::BUSINESS_VIEW,

                PermissionRegistry::TEAM_VIEW,

                PermissionRegistry::CUSTOMERS_VIEW,

                PermissionRegistry::PRODUCTS_VIEW,

                PermissionRegistry::CATEGORIES_VIEW,

                PermissionRegistry::KNOWLEDGE_VIEW,

                PermissionRegistry::WHATSAPP_VIEW,

                PermissionRegistry::CONVERSATIONS_VIEW,

                PermissionRegistry::ANALYTICS_VIEW,

                PermissionRegistry::BROADCASTS_VIEW,

                PermissionRegistry::NOTIFICATIONS_VIEW,

                PermissionRegistry::SETTINGS_VIEW,
            ],

            default => [],
        };
    }

    public static function roleGroup(string $role): string
    {
        return match ($role) {
            self::SUPER_ADMIN => 'global',
            default => 'tenant',
        };
    }
}
