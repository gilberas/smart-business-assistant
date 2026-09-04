<?php

namespace App\Support;

final class PermissionRegistry
{
    const SUPER_ADMIN = 'super.admin';

    const BUSINESS_VIEW = 'business.view';
    const BUSINESS_CREATE = 'business.create';
    const BUSINESS_UPDATE = 'business.update';
    const BUSINESS_DELETE = 'business.delete';
    const BUSINESS_SETTINGS_MANAGE = 'business.settings.manage';

    const TEAM_VIEW = 'team.view';
    const TEAM_INVITE = 'team.invite';
    const TEAM_ROLE_UPDATE = 'team.role.update';
    const TEAM_REMOVE = 'team.remove';

    const CUSTOMERS_VIEW = 'customers.view';
    const CUSTOMERS_CREATE = 'customers.create';
    const CUSTOMERS_UPDATE = 'customers.update';
    const CUSTOMERS_DELETE = 'customers.delete';
    const CUSTOMERS_EXPORT = 'customers.export';
    const CUSTOMERS_IMPORT = 'customers.import';
    const CUSTOMERS_MERGE = 'customers.merge';

    const PRODUCTS_VIEW = 'products.view';
    const PRODUCTS_CREATE = 'products.create';
    const PRODUCTS_UPDATE = 'products.update';
    const PRODUCTS_DELETE = 'products.delete';

    const CATEGORIES_VIEW = 'categories.view';
    const CATEGORIES_CREATE = 'categories.create';
    const CATEGORIES_UPDATE = 'categories.update';
    const CATEGORIES_DELETE = 'categories.delete';

    const KNOWLEDGE_VIEW = 'knowledge.view';
    const KNOWLEDGE_CREATE = 'knowledge.create';
    const KNOWLEDGE_UPDATE = 'knowledge.update';
    const KNOWLEDGE_DELETE = 'knowledge.delete';

    const WHATSAPP_VIEW = 'whatsapp.view';
    const WHATSAPP_SEND = 'whatsapp.send';
    const WHATSAPP_TEMPLATES_MANAGE = 'whatsapp.templates.manage';
    const WHATSAPP_PHONE_MANAGE = 'whatsapp.phone.manage';
    const WHATSAPP_WEBHOOKS_MANAGE = 'whatsapp.webhooks.manage';

    const CONVERSATIONS_VIEW = 'conversations.view';
    const CONVERSATIONS_CREATE = 'conversations.create';
    const CONVERSATIONS_UPDATE = 'conversations.update';
    const CONVERSATIONS_REPLY = 'conversations.reply';
    const CONVERSATIONS_ASSIGN = 'conversations.assign';
    const CONVERSATIONS_CLOSE = 'conversations.close';
    const CONVERSATIONS_REOPEN = 'conversations.reopen';
    const CONVERSATIONS_DELETE = 'conversations.delete';
    const CONVERSATIONS_MANAGE_LABELS = 'conversations.manage_labels';

    const AI_CHAT = 'ai.chat';
    const AI_TRAIN = 'ai.train';
    const AI_CONFIGURE = 'ai.configure';
    const AI_VIEW_USAGE = 'ai.view_usage';
    const AI_MANAGE_PROVIDERS = 'ai.manage_providers';
    const AI_MANAGE_PROMPTS = 'ai.manage_prompts';

    const WORKFLOWS_VIEW = 'workflows.view';
    const WORKFLOWS_CREATE = 'workflows.create';
    const WORKFLOWS_UPDATE = 'workflows.update';
    const WORKFLOWS_DELETE = 'workflows.delete';
    const WORKFLOWS_EXECUTE = 'workflows.execute';
    const WORKFLOWS_MANAGE = 'workflows.manage';

    const ANALYTICS_VIEW = 'analytics.view';
    const ANALYTICS_EXPORT = 'analytics.export';

    const BROADCASTS_VIEW = 'broadcasts.view';
    const BROADCASTS_CREATE = 'broadcasts.create';
    const BROADCASTS_SEND = 'broadcasts.send';
    const BROADCASTS_DELETE = 'broadcasts.delete';

    const NOTIFICATIONS_VIEW = 'notifications.view';
    const NOTIFICATIONS_MANAGE = 'notifications.manage';

    const SETTINGS_VIEW = 'settings.view';
    const SETTINGS_MANAGE = 'settings.manage';

    const AUDIT_VIEW = 'audit.view';
    const AUDIT_EXPORT = 'audit.export';

    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,

            self::BUSINESS_VIEW,
            self::BUSINESS_CREATE,
            self::BUSINESS_UPDATE,
            self::BUSINESS_DELETE,
            self::BUSINESS_SETTINGS_MANAGE,

            self::TEAM_VIEW,
            self::TEAM_INVITE,
            self::TEAM_ROLE_UPDATE,
            self::TEAM_REMOVE,

            self::CUSTOMERS_VIEW,
            self::CUSTOMERS_CREATE,
            self::CUSTOMERS_UPDATE,
            self::CUSTOMERS_DELETE,
            self::CUSTOMERS_EXPORT,
            self::CUSTOMERS_IMPORT,
            self::CUSTOMERS_MERGE,

            self::PRODUCTS_VIEW,
            self::PRODUCTS_CREATE,
            self::PRODUCTS_UPDATE,
            self::PRODUCTS_DELETE,

            self::CATEGORIES_VIEW,
            self::CATEGORIES_CREATE,
            self::CATEGORIES_UPDATE,
            self::CATEGORIES_DELETE,

            self::KNOWLEDGE_VIEW,
            self::KNOWLEDGE_CREATE,
            self::KNOWLEDGE_UPDATE,
            self::KNOWLEDGE_DELETE,

            self::WHATSAPP_VIEW,
            self::WHATSAPP_SEND,
            self::WHATSAPP_TEMPLATES_MANAGE,
            self::WHATSAPP_PHONE_MANAGE,
            self::WHATSAPP_WEBHOOKS_MANAGE,

            self::CONVERSATIONS_VIEW,
            self::CONVERSATIONS_CREATE,
            self::CONVERSATIONS_UPDATE,
            self::CONVERSATIONS_REPLY,
            self::CONVERSATIONS_ASSIGN,
            self::CONVERSATIONS_CLOSE,
            self::CONVERSATIONS_REOPEN,
            self::CONVERSATIONS_DELETE,
            self::CONVERSATIONS_MANAGE_LABELS,

            self::AI_CHAT,
            self::AI_TRAIN,
            self::AI_CONFIGURE,
            self::AI_VIEW_USAGE,
            self::AI_MANAGE_PROVIDERS,
            self::AI_MANAGE_PROMPTS,

            self::WORKFLOWS_VIEW,
            self::WORKFLOWS_CREATE,
            self::WORKFLOWS_UPDATE,
            self::WORKFLOWS_DELETE,
            self::WORKFLOWS_EXECUTE,
            self::WORKFLOWS_MANAGE,

            self::ANALYTICS_VIEW,
            self::ANALYTICS_EXPORT,

            self::BROADCASTS_VIEW,
            self::BROADCASTS_CREATE,
            self::BROADCASTS_SEND,
            self::BROADCASTS_DELETE,

            self::NOTIFICATIONS_VIEW,
            self::NOTIFICATIONS_MANAGE,

            self::SETTINGS_VIEW,
            self::SETTINGS_MANAGE,

            self::AUDIT_VIEW,
            self::AUDIT_EXPORT,
        ];
    }

    public static function groups(): array
    {
        return [
            'Super Admin' => [self::SUPER_ADMIN],
            'Business' => [
                self::BUSINESS_VIEW,
                self::BUSINESS_CREATE,
                self::BUSINESS_UPDATE,
                self::BUSINESS_DELETE,
                self::BUSINESS_SETTINGS_MANAGE,
            ],
            'Team' => [
                self::TEAM_VIEW,
                self::TEAM_INVITE,
                self::TEAM_ROLE_UPDATE,
                self::TEAM_REMOVE,
            ],
            'Customers' => [
                self::CUSTOMERS_VIEW,
                self::CUSTOMERS_CREATE,
                self::CUSTOMERS_UPDATE,
                self::CUSTOMERS_DELETE,
                self::CUSTOMERS_EXPORT,
                self::CUSTOMERS_IMPORT,
                self::CUSTOMERS_MERGE,
            ],
            'Products' => [
                self::PRODUCTS_VIEW,
                self::PRODUCTS_CREATE,
                self::PRODUCTS_UPDATE,
                self::PRODUCTS_DELETE,
            ],
            'Categories' => [
                self::CATEGORIES_VIEW,
                self::CATEGORIES_CREATE,
                self::CATEGORIES_UPDATE,
                self::CATEGORIES_DELETE,
            ],
            'Knowledge Base' => [
                self::KNOWLEDGE_VIEW,
                self::KNOWLEDGE_CREATE,
                self::KNOWLEDGE_UPDATE,
                self::KNOWLEDGE_DELETE,
            ],
            'WhatsApp' => [
                self::WHATSAPP_VIEW,
                self::WHATSAPP_SEND,
                self::WHATSAPP_TEMPLATES_MANAGE,
                self::WHATSAPP_PHONE_MANAGE,
                self::WHATSAPP_WEBHOOKS_MANAGE,
            ],
            'Conversations' => [
                self::CONVERSATIONS_VIEW,
                self::CONVERSATIONS_CREATE,
                self::CONVERSATIONS_UPDATE,
                self::CONVERSATIONS_REPLY,
                self::CONVERSATIONS_ASSIGN,
                self::CONVERSATIONS_CLOSE,
                self::CONVERSATIONS_REOPEN,
                self::CONVERSATIONS_DELETE,
                self::CONVERSATIONS_MANAGE_LABELS,
            ],
            'AI' => [
                self::AI_CHAT,
                self::AI_TRAIN,
                self::AI_CONFIGURE,
                self::AI_VIEW_USAGE,
                self::AI_MANAGE_PROVIDERS,
                self::AI_MANAGE_PROMPTS,
            ],
            'Workflows' => [
                self::WORKFLOWS_VIEW,
                self::WORKFLOWS_CREATE,
                self::WORKFLOWS_UPDATE,
                self::WORKFLOWS_DELETE,
                self::WORKFLOWS_EXECUTE,
                self::WORKFLOWS_MANAGE,
            ],
            'Analytics' => [
                self::ANALYTICS_VIEW,
                self::ANALYTICS_EXPORT,
            ],
            'Broadcasts' => [
                self::BROADCASTS_VIEW,
                self::BROADCASTS_CREATE,
                self::BROADCASTS_SEND,
                self::BROADCASTS_DELETE,
            ],
            'Notifications' => [
                self::NOTIFICATIONS_VIEW,
                self::NOTIFICATIONS_MANAGE,
            ],
            'Settings' => [
                self::SETTINGS_VIEW,
                self::SETTINGS_MANAGE,
            ],
            'Audit' => [
                self::AUDIT_VIEW,
                self::AUDIT_EXPORT,
            ],
        ];
    }

    public static function groupFor(string $permission): ?string
    {
        foreach (self::groups() as $group => $permissions) {
            if (in_array($permission, $permissions, true)) {
                return $group;
            }
        }

        return null;
    }

    public static function label(string $permission): string
    {
        $labels = [
            self::SUPER_ADMIN => 'Super Admin Access',

            self::BUSINESS_VIEW => 'View Business',
            self::BUSINESS_CREATE => 'Create Business',
            self::BUSINESS_UPDATE => 'Update Business',
            self::BUSINESS_DELETE => 'Delete Business',
            self::BUSINESS_SETTINGS_MANAGE => 'Manage Business Settings',

            self::TEAM_VIEW => 'View Team Members',
            self::TEAM_INVITE => 'Invite Team Members',
            self::TEAM_ROLE_UPDATE => 'Update Member Roles',
            self::TEAM_REMOVE => 'Remove Team Members',

            self::CUSTOMERS_VIEW => 'View Customers',
            self::CUSTOMERS_CREATE => 'Create Customers',
            self::CUSTOMERS_UPDATE => 'Update Customers',
            self::CUSTOMERS_DELETE => 'Delete Customers',
            self::CUSTOMERS_EXPORT => 'Export Customers',
            self::CUSTOMERS_IMPORT => 'Import Customers',
            self::CUSTOMERS_MERGE => 'Merge Customers',

            self::PRODUCTS_VIEW => 'View Products',
            self::PRODUCTS_CREATE => 'Create Products',
            self::PRODUCTS_UPDATE => 'Update Products',
            self::PRODUCTS_DELETE => 'Delete Products',

            self::CATEGORIES_VIEW => 'View Categories',
            self::CATEGORIES_CREATE => 'Create Categories',
            self::CATEGORIES_UPDATE => 'Update Categories',
            self::CATEGORIES_DELETE => 'Delete Categories',

            self::KNOWLEDGE_VIEW => 'View Knowledge Base',
            self::KNOWLEDGE_CREATE => 'Create Articles',
            self::KNOWLEDGE_UPDATE => 'Update Articles',
            self::KNOWLEDGE_DELETE => 'Delete Articles',

            self::WHATSAPP_VIEW => 'View WhatsApp',
            self::WHATSAPP_SEND => 'Send WhatsApp Messages',
            self::WHATSAPP_TEMPLATES_MANAGE => 'Manage Templates',
            self::WHATSAPP_PHONE_MANAGE => 'Manage Phone Numbers',
            self::WHATSAPP_WEBHOOKS_MANAGE => 'Manage Webhooks',

            self::CONVERSATIONS_VIEW => 'View Conversations',
            self::CONVERSATIONS_CREATE => 'Create Conversations',
            self::CONVERSATIONS_UPDATE => 'Update Conversations',
            self::CONVERSATIONS_REPLY => 'Reply to Conversations',
            self::CONVERSATIONS_ASSIGN => 'Assign Conversations',
            self::CONVERSATIONS_CLOSE => 'Close Conversations',
            self::CONVERSATIONS_REOPEN => 'Reopen Conversations',
            self::CONVERSATIONS_DELETE => 'Delete Conversations',
            self::CONVERSATIONS_MANAGE_LABELS => 'Manage Labels',

            self::AI_CHAT => 'Use AI Chat',
            self::AI_TRAIN => 'Train AI Models',
            self::AI_CONFIGURE => 'Configure AI Settings',
            self::AI_VIEW_USAGE => 'View AI Usage',
            self::AI_MANAGE_PROVIDERS => 'Manage AI Providers',
            self::AI_MANAGE_PROMPTS => 'Manage AI Prompts',

            self::WORKFLOWS_VIEW => 'View Workflows',
            self::WORKFLOWS_CREATE => 'Create Workflows',
            self::WORKFLOWS_UPDATE => 'Update Workflows',
            self::WORKFLOWS_DELETE => 'Delete Workflows',
            self::WORKFLOWS_EXECUTE => 'Execute Workflows',
            self::WORKFLOWS_MANAGE => 'Manage Workflows',

            self::ANALYTICS_VIEW => 'View Analytics',
            self::ANALYTICS_EXPORT => 'Export Analytics',

            self::BROADCASTS_VIEW => 'View Broadcasts',
            self::BROADCASTS_CREATE => 'Create Broadcasts',
            self::BROADCASTS_SEND => 'Send Broadcasts',
            self::BROADCASTS_DELETE => 'Delete Broadcasts',

            self::NOTIFICATIONS_VIEW => 'View Notifications',
            self::NOTIFICATIONS_MANAGE => 'Manage Notifications',

            self::SETTINGS_VIEW => 'View Settings',
            self::SETTINGS_MANAGE => 'Manage Settings',

            self::AUDIT_VIEW => 'View Audit Log',
            self::AUDIT_EXPORT => 'Export Audit Log',
        ];

        return $labels[$permission] ?? $permission;
    }
}
