export const PERMISSIONS = {
    SUPER_ADMIN: 'super.admin',

    BUSINESS_VIEW: 'business.view',
    BUSINESS_CREATE: 'business.create',
    BUSINESS_UPDATE: 'business.update',
    BUSINESS_DELETE: 'business.delete',
    BUSINESS_SETTINGS_MANAGE: 'business.settings.manage',

    TEAM_VIEW: 'team.view',
    TEAM_INVITE: 'team.invite',
    TEAM_ROLE_UPDATE: 'team.role.update',
    TEAM_REMOVE: 'team.remove',

    CUSTOMERS_VIEW: 'customers.view',
    CUSTOMERS_CREATE: 'customers.create',
    CUSTOMERS_UPDATE: 'customers.update',
    CUSTOMERS_DELETE: 'customers.delete',

    PRODUCTS_VIEW: 'products.view',
    PRODUCTS_CREATE: 'products.create',
    PRODUCTS_UPDATE: 'products.update',
    PRODUCTS_DELETE: 'products.delete',

    CATEGORIES_VIEW: 'categories.view',
    CATEGORIES_CREATE: 'categories.create',
    CATEGORIES_UPDATE: 'categories.update',
    CATEGORIES_DELETE: 'categories.delete',

    KNOWLEDGE_VIEW: 'knowledge.view',
    KNOWLEDGE_CREATE: 'knowledge.create',
    KNOWLEDGE_UPDATE: 'knowledge.update',
    KNOWLEDGE_DELETE: 'knowledge.delete',

    WHATSAPP_VIEW: 'whatsapp.view',
    WHATSAPP_SEND: 'whatsapp.send',
    WHATSAPP_TEMPLATES_MANAGE: 'whatsapp.templates.manage',
    WHATSAPP_PHONE_MANAGE: 'whatsapp.phone.manage',
    WHATSAPP_WEBHOOKS_MANAGE: 'whatsapp.webhooks.manage',

    CONVERSATIONS_VIEW: 'conversations.view',
    CONVERSATIONS_REPLY: 'conversations.reply',
    CONVERSATIONS_ASSIGN: 'conversations.assign',
    CONVERSATIONS_CLOSE: 'conversations.close',
    CONVERSATIONS_DELETE: 'conversations.delete',

    AI_CHAT: 'ai.chat',
    AI_TRAIN: 'ai.train',
    AI_CONFIGURE: 'ai.configure',

    ANALYTICS_VIEW: 'analytics.view',
    ANALYTICS_EXPORT: 'analytics.export',

    BROADCASTS_VIEW: 'broadcasts.view',
    BROADCASTS_CREATE: 'broadcasts.create',
    BROADCASTS_SEND: 'broadcasts.send',
    BROADCASTS_DELETE: 'broadcasts.delete',

    NOTIFICATIONS_VIEW: 'notifications.view',
    NOTIFICATIONS_MANAGE: 'notifications.manage',

    SETTINGS_VIEW: 'settings.view',
    SETTINGS_MANAGE: 'settings.manage',

    AUDIT_VIEW: 'audit.view',
    AUDIT_EXPORT: 'audit.export',
} as const;

export type Permission = (typeof PERMISSIONS)[keyof typeof PERMISSIONS];

export const ROLES = {
    SUPER_ADMIN: 'super-admin',
    BUSINESS_OWNER: 'business-owner',
    BUSINESS_ADMIN: 'business-admin',
    MANAGER: 'manager',
    AGENT: 'agent',
    VIEWER: 'viewer',
} as const;

export type Role = (typeof ROLES)[keyof typeof ROLES];

export const ROLE_HIERARCHY: Record<Role, number> = {
    [ROLES.SUPER_ADMIN]: 100,
    [ROLES.BUSINESS_OWNER]: 80,
    [ROLES.BUSINESS_ADMIN]: 60,
    [ROLES.MANAGER]: 40,
    [ROLES.AGENT]: 20,
    [ROLES.VIEWER]: 10,
};

export const ROLE_LABELS: Record<Role, string> = {
    [ROLES.SUPER_ADMIN]: 'Super Admin',
    [ROLES.BUSINESS_OWNER]: 'Business Owner',
    [ROLES.BUSINESS_ADMIN]: 'Administrator',
    [ROLES.MANAGER]: 'Manager',
    [ROLES.AGENT]: 'Agent',
    [ROLES.VIEWER]: 'Viewer',
};

export const ROLE_DESCRIPTIONS: Record<Role, string> = {
    [ROLES.SUPER_ADMIN]: 'Global platform administrator with unrestricted access.',
    [ROLES.BUSINESS_OWNER]: 'Full business ownership including deletion and billing.',
    [ROLES.BUSINESS_ADMIN]: 'Complete business management except deletion.',
    [ROLES.MANAGER]: 'Operational management with limited administrative access.',
    [ROLES.AGENT]: 'Daily operations including customer interaction.',
    [ROLES.VIEWER]: 'Read-only access to business data.',
};

export const PERMISSION_GROUPS: Record<string, Permission[]> = {
    'Super Admin': [PERMISSIONS.SUPER_ADMIN],
    Business: [
        PERMISSIONS.BUSINESS_VIEW,
        PERMISSIONS.BUSINESS_CREATE,
        PERMISSIONS.BUSINESS_UPDATE,
        PERMISSIONS.BUSINESS_DELETE,
        PERMISSIONS.BUSINESS_SETTINGS_MANAGE,
    ],
    Team: [
        PERMISSIONS.TEAM_VIEW,
        PERMISSIONS.TEAM_INVITE,
        PERMISSIONS.TEAM_ROLE_UPDATE,
        PERMISSIONS.TEAM_REMOVE,
    ],
    Customers: [
        PERMISSIONS.CUSTOMERS_VIEW,
        PERMISSIONS.CUSTOMERS_CREATE,
        PERMISSIONS.CUSTOMERS_UPDATE,
        PERMISSIONS.CUSTOMERS_DELETE,
    ],
    Products: [
        PERMISSIONS.PRODUCTS_VIEW,
        PERMISSIONS.PRODUCTS_CREATE,
        PERMISSIONS.PRODUCTS_UPDATE,
        PERMISSIONS.PRODUCTS_DELETE,
    ],
    'Knowledge Base': [
        PERMISSIONS.KNOWLEDGE_VIEW,
        PERMISSIONS.KNOWLEDGE_CREATE,
        PERMISSIONS.KNOWLEDGE_UPDATE,
        PERMISSIONS.KNOWLEDGE_DELETE,
    ],
    WhatsApp: [
        PERMISSIONS.WHATSAPP_VIEW,
        PERMISSIONS.WHATSAPP_SEND,
        PERMISSIONS.WHATSAPP_TEMPLATES_MANAGE,
        PERMISSIONS.WHATSAPP_PHONE_MANAGE,
        PERMISSIONS.WHATSAPP_WEBHOOKS_MANAGE,
    ],
    Conversations: [
        PERMISSIONS.CONVERSATIONS_VIEW,
        PERMISSIONS.CONVERSATIONS_REPLY,
        PERMISSIONS.CONVERSATIONS_ASSIGN,
        PERMISSIONS.CONVERSATIONS_CLOSE,
        PERMISSIONS.CONVERSATIONS_DELETE,
    ],
    AI: [
        PERMISSIONS.AI_CHAT,
        PERMISSIONS.AI_TRAIN,
        PERMISSIONS.AI_CONFIGURE,
    ],
    Analytics: [
        PERMISSIONS.ANALYTICS_VIEW,
        PERMISSIONS.ANALYTICS_EXPORT,
    ],
    Broadcasts: [
        PERMISSIONS.BROADCASTS_VIEW,
        PERMISSIONS.BROADCASTS_CREATE,
        PERMISSIONS.BROADCASTS_SEND,
        PERMISSIONS.BROADCASTS_DELETE,
    ],
    Notifications: [
        PERMISSIONS.NOTIFICATIONS_VIEW,
        PERMISSIONS.NOTIFICATIONS_MANAGE,
    ],
    Settings: [
        PERMISSIONS.SETTINGS_VIEW,
        PERMISSIONS.SETTINGS_MANAGE,
    ],
    Audit: [
        PERMISSIONS.AUDIT_VIEW,
        PERMISSIONS.AUDIT_EXPORT,
    ],
};

export interface AuthorizationState {
    permissions: string[];
    role: Role | null;
    isSuperAdmin: boolean;
}

export type AuthorizationResponse = AuthorizationState;
