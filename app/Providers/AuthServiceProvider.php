<?php

namespace App\Providers;

use App\Contracts\AuthorizationServiceInterface;
use App\Models\Business;
use App\Models\BusinessInformation;
use App\Models\Customer;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeCollection;
use App\Models\KnowledgeFaq;
use App\Models\KnowledgeMedium;
use App\Models\KnowledgeTag;
use App\Models\Conversation;
use App\Models\ConversationLabel;
use App\Models\User;
use App\Policies\BusinessPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\KnowledgePolicy;
use App\Policies\WorkflowPolicy;
use App\Policies\UserPolicy;
use App\Policies\WhatsAppPolicy;
use App\Support\PermissionRegistry;
use App\Support\RoleRegistry;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Business::class => BusinessPolicy::class,
        Customer::class => CustomerPolicy::class,
        User::class => UserPolicy::class,
        KnowledgeArticle::class => KnowledgePolicy::class,
        KnowledgeCategory::class => KnowledgePolicy::class,
        KnowledgeTag::class => KnowledgePolicy::class,
        KnowledgeFaq::class => KnowledgePolicy::class,
        KnowledgeCollection::class => KnowledgePolicy::class,
        BusinessInformation::class => KnowledgePolicy::class,
        KnowledgeMedium::class => KnowledgePolicy::class,
        \App\Models\WhatsApp\WhatsAppAccount::class => WhatsAppPolicy::class,
        \App\Models\WhatsApp\PhoneNumber::class => WhatsAppPolicy::class,
        \App\Models\WhatsApp\Message::class => WhatsAppPolicy::class,
        \App\Models\WhatsApp\Media::class => WhatsAppPolicy::class,
        Conversation::class => \App\Policies\ConversationPolicy::class,
        ConversationLabel::class => \App\Policies\ConversationPolicy::class,
        \App\Models\Workflow::class => WorkflowPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        $this->configureGates();
        $this->configureRateLimiting();
    }

    protected function configureGates(): void
    {
        Gate::before(function (User $user) {
            $authService = app(AuthorizationServiceInterface::class);

            if ($authService->isSuperAdmin($user)) {
                return true;
            }
        });

        Gate::define('viewBusiness', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::BUSINESS_VIEW, $business);
        });

        Gate::define('manageBusiness', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->isAdminOrAbove($user, $business);
        });

        Gate::define('manageTeam', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::TEAM_INVITE, $business);
        });

        Gate::define('manageSettings', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::SETTINGS_MANAGE, $business);
        });

        Gate::define('deleteBusiness', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->isOwner($user, $business);
        });

        Gate::define('exportData', function (User $user) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::ANALYTICS_EXPORT);
        });

        Gate::define('viewAnalytics', function (User $user) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::ANALYTICS_VIEW);
        });

        Gate::define('viewAuditLog', function (User $user) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::AUDIT_VIEW);
        });

        Gate::define('manageNotifications', function (User $user) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::NOTIFICATIONS_MANAGE);
        });

        Gate::define('viewCustomers', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::CUSTOMERS_VIEW, $business);
        });

        Gate::define('createCustomers', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::CUSTOMERS_CREATE, $business);
        });

        Gate::define('updateCustomers', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::CUSTOMERS_UPDATE, $business);
        });

        Gate::define('deleteCustomers', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::CUSTOMERS_DELETE, $business);
        });

        Gate::define('exportCustomers', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::CUSTOMERS_EXPORT, $business);
        });

        Gate::define('importCustomers', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::CUSTOMERS_IMPORT, $business);
        });

        Gate::define('mergeCustomers', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::CUSTOMERS_MERGE, $business);
        });

        Gate::define('viewKnowledge', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::KNOWLEDGE_VIEW, $business);
        });

        Gate::define('createKnowledge', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::KNOWLEDGE_CREATE, $business);
        });

        Gate::define('updateKnowledge', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::KNOWLEDGE_UPDATE, $business);
        });

        Gate::define('deleteKnowledge', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::KNOWLEDGE_DELETE, $business);
        });

        Gate::define('viewWhatsApp', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::WHATSAPP_VIEW, $business);
        });

        Gate::define('sendWhatsApp', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::WHATSAPP_SEND, $business);
        });

        Gate::define('manageWhatsAppTemplates', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::WHATSAPP_TEMPLATES_MANAGE, $business);
        });

        Gate::define('manageWhatsAppPhones', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::WHATSAPP_PHONE_MANAGE, $business);
        });

        Gate::define('manageWhatsAppWebhooks', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::WHATSAPP_WEBHOOKS_MANAGE, $business);
        });

        Gate::define('manageWorkflows', function (User $user, Business $business) {
            return app(AuthorizationServiceInterface::class)->can($user, PermissionRegistry::WORKFLOWS_MANAGE, $business);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('authorization', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
