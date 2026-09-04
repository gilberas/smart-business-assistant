<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Http\Controllers\Api\V1\Authorization\AuthorizationController;
use App\Http\Controllers\Api\V1\Authorization\PermissionController;
use App\Http\Controllers\Api\V1\Authorization\RoleController;
use App\Http\Controllers\Api\V1\Business\BusinessController;
use App\Http\Controllers\Api\V1\Business\BusinessOnboardingController;
use App\Http\Controllers\Api\V1\Business\MemberController;
use App\Http\Controllers\Api\V1\Business\SettingsController;
use App\Http\Controllers\Api\V1\Customer\CustomerActivityController;
use App\Http\Controllers\Api\V1\Customer\CustomerController;
use App\Http\Controllers\Api\V1\Customer\CustomerCustomFieldController;
use App\Http\Controllers\Api\V1\Customer\CustomerExportController;
use App\Http\Controllers\Api\V1\Customer\CustomerImportController;
use App\Http\Controllers\Api\V1\Customer\CustomerMergeController;
use App\Http\Controllers\Api\V1\Customer\CustomerNoteController;
use App\Http\Controllers\Api\V1\Customer\CustomerTagAttachController;
use App\Http\Controllers\Api\V1\Customer\CustomerTagController;
use App\Http\Controllers\Api\V1\Knowledge\BusinessInformationController;
use App\Http\Controllers\Api\V1\Knowledge\KnowledgeArticleController;
use App\Http\Controllers\Api\V1\Knowledge\KnowledgeCategoryController;
use App\Http\Controllers\Api\V1\Knowledge\KnowledgeCollectionController;
use App\Http\Controllers\Api\V1\Knowledge\KnowledgeFaqController;
use App\Http\Controllers\Api\V1\Knowledge\KnowledgeMediaController;
use App\Http\Controllers\Api\V1\Knowledge\KnowledgeTagController;
use App\Http\Controllers\Api\V1\Inbox\InboxController;
use App\Http\Controllers\Api\V1\Workflow\WorkflowController;
use App\Http\Controllers\Api\V1\Team\TeamController;
use App\Http\Controllers\Api\V1\WhatsApp\MediaController as WhatsAppMediaController;
use App\Http\Controllers\Api\V1\WhatsApp\MessageController;
use App\Http\Controllers\Api\V1\WhatsApp\WhatsAppAccountController;
use App\Http\Controllers\Api\V1\WhatsApp\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->name('verification.verify');
        Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout/all', [AuthController::class, 'logoutAllDevices']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::post('/confirm-password', [AuthController::class, 'confirmPassword']);
            Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);

            Route::prefix('profile')->group(function () {
                Route::put('/', [ProfileController::class, 'update']);
                Route::put('/password', [ProfileController::class, 'changePassword']);
                Route::delete('/', [ProfileController::class, 'destroy']);
            });
        });
    });

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::get('/businesses', [BusinessController::class, 'index']);
        Route::post('/businesses', [BusinessController::class, 'store']);

        Route::get('/business/current', [BusinessController::class, 'current']);
        Route::post('/business/switch', [BusinessController::class, 'switch']);

        Route::middleware('business.selected')->group(function () {
            Route::get('/businesses/{business}', [BusinessController::class, 'show']);
            Route::put('/businesses/{business}', [BusinessController::class, 'update']);
            Route::delete('/businesses/{business}', [BusinessController::class, 'destroy']);

            Route::get('/businesses/{business}/members', [MemberController::class, 'index']);
            Route::post('/businesses/{business}/members', [MemberController::class, 'store']);
            Route::put('/businesses/{business}/members/{member}', [MemberController::class, 'update']);
            Route::delete('/businesses/{business}/members/{member}', [MemberController::class, 'destroy']);

            Route::post('/businesses/{business}/onboarding', [BusinessOnboardingController::class, 'update']);
            Route::post('/businesses/{business}/onboarding/complete', [BusinessOnboardingController::class, 'complete']);

            Route::get('/businesses/{business}/settings', [SettingsController::class, 'show']);
            Route::put('/businesses/{business}/settings', [SettingsController::class, 'update']);

            Route::get('/team/roles', [TeamController::class, 'roles']);
            Route::post('/team/invitations', [TeamController::class, 'invite']);
            Route::get('/team/invitations', [TeamController::class, 'invitations']);
            Route::delete('/team/invitations/{invitation}', [TeamController::class, 'cancelInvitation']);
            Route::post('/team/invitations/{invitation}/resend', [TeamController::class, 'resendInvitation']);

            Route::get('/team/members', [TeamController::class, 'members']);
            Route::put('/team/members/{member}/role', [TeamController::class, 'changeRole']);
            Route::delete('/team/members/{member}', [TeamController::class, 'removeMember']);
            Route::post('/team/members/{member}/suspend', [TeamController::class, 'suspendMember']);
            Route::post('/team/members/{member}/restore', [TeamController::class, 'restoreMember']);

            Route::post('/team/transfer-ownership', [TeamController::class, 'transferOwnership']);
            Route::post('/team/leave', [TeamController::class, 'leave']);
            Route::get('/team/activity', [TeamController::class, 'activity']);
        });
    });

    Route::post('/invitations/{token}/accept', [TeamController::class, 'acceptInvitation']);
    Route::post('/invitations/{token}/reject', [TeamController::class, 'rejectInvitation']);

    // Public WhatsApp webhook routes (called by Meta)
    Route::prefix('whatsapp')->group(function () {
        Route::get('/webhook/{business}', [WebhookController::class, 'verify'])
            ->whereUuid('business')->name('whatsapp.webhook.verify');
        Route::post('/webhook/{business}', [WebhookController::class, 'handle'])
            ->whereUuid('business')->name('whatsapp.webhook.handle');
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('customers')->group(function () {
        Route::get('/', [CustomerController::class, 'index']);
        Route::post('/', [CustomerController::class, 'store']);
        Route::get('/search', [CustomerController::class, 'search']);
        Route::get('/stats', [CustomerController::class, 'stats']);
        Route::get('/export', [CustomerExportController::class, 'export']);
        Route::get('/export/headers', [CustomerExportController::class, 'headers']);
        Route::post('/import/preview', [CustomerImportController::class, 'preview']);
        Route::post('/import', [CustomerImportController::class, 'import']);
        Route::post('/merge', [CustomerMergeController::class, 'merge']);

        Route::get('/{customer}', [CustomerController::class, 'show']);
        Route::put('/{customer}', [CustomerController::class, 'update']);
        Route::delete('/{customer}', [CustomerController::class, 'destroy']);
        Route::post('/{customer}/archive', [CustomerController::class, 'archive']);
        Route::post('/{customer}/restore', [CustomerController::class, 'restore']);

        Route::post('/{customer}/tags/attach', [CustomerTagAttachController::class, 'attach']);
        Route::post('/{customer}/tags/detach', [CustomerTagAttachController::class, 'detach']);

        Route::get('/{customer}/notes', [CustomerNoteController::class, 'index']);
        Route::post('/{customer}/notes', [CustomerNoteController::class, 'store']);
        Route::put('/{customer}/notes/{note}', [CustomerNoteController::class, 'update']);
        Route::delete('/{customer}/notes/{note}', [CustomerNoteController::class, 'destroy']);
        Route::post('/{customer}/notes/{note}/pin', [CustomerNoteController::class, 'togglePin']);

        Route::get('/{customer}/activity', [CustomerActivityController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('tags')->group(function () {
        Route::get('/', [CustomerTagController::class, 'index']);
        Route::post('/', [CustomerTagController::class, 'store']);
        Route::get('/{tag}', [CustomerTagController::class, 'show']);
        Route::put('/{tag}', [CustomerTagController::class, 'update']);
        Route::delete('/{tag}', [CustomerTagController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('custom-fields')->group(function () {
        Route::get('/', [CustomerCustomFieldController::class, 'index']);
        Route::post('/', [CustomerCustomFieldController::class, 'store']);
        Route::delete('/{customField}', [CustomerCustomFieldController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('knowledge')->group(function () {
        Route::get('/articles', [KnowledgeArticleController::class, 'index']);
        Route::post('/articles', [KnowledgeArticleController::class, 'store']);
        Route::get('/articles/search', [KnowledgeArticleController::class, 'search']);
        Route::get('/articles/stats', [KnowledgeArticleController::class, 'stats']);
        Route::get('/articles/{article}', [KnowledgeArticleController::class, 'show']);
        Route::put('/articles/{article}', [KnowledgeArticleController::class, 'update']);
        Route::delete('/articles/{article}', [KnowledgeArticleController::class, 'destroy']);
        Route::post('/articles/{article}/publish', [KnowledgeArticleController::class, 'publish']);
        Route::post('/articles/{article}/archive', [KnowledgeArticleController::class, 'archive']);
        Route::post('/articles/{article}/restore', [KnowledgeArticleController::class, 'restore']);
        Route::post('/articles/{article}/view', [KnowledgeArticleController::class, 'trackView']);
        Route::post('/articles/{article}/helpful', [KnowledgeArticleController::class, 'markHelpful']);
        Route::post('/articles/{article}/not-helpful', [KnowledgeArticleController::class, 'markNotHelpful']);

        Route::get('/categories', [KnowledgeCategoryController::class, 'index']);
        Route::get('/categories/tree', [KnowledgeCategoryController::class, 'tree']);
        Route::post('/categories', [KnowledgeCategoryController::class, 'store']);
        Route::get('/categories/{category}', [KnowledgeCategoryController::class, 'show']);
        Route::put('/categories/{category}', [KnowledgeCategoryController::class, 'update']);
        Route::delete('/categories/{category}', [KnowledgeCategoryController::class, 'destroy']);

        Route::get('/tags', [KnowledgeTagController::class, 'index']);
        Route::post('/tags', [KnowledgeTagController::class, 'store']);
        Route::get('/tags/{tag}', [KnowledgeTagController::class, 'show']);
        Route::put('/tags/{tag}', [KnowledgeTagController::class, 'update']);
        Route::delete('/tags/{tag}', [KnowledgeTagController::class, 'destroy']);

        Route::get('/faqs', [KnowledgeFaqController::class, 'index']);
        Route::post('/faqs', [KnowledgeFaqController::class, 'store']);
        Route::get('/faqs/{faq}', [KnowledgeFaqController::class, 'show']);
        Route::put('/faqs/{faq}', [KnowledgeFaqController::class, 'update']);
        Route::delete('/faqs/{faq}', [KnowledgeFaqController::class, 'destroy']);

        Route::get('/collections', [KnowledgeCollectionController::class, 'index']);
        Route::post('/collections', [KnowledgeCollectionController::class, 'store']);
        Route::get('/collections/{collection}', [KnowledgeCollectionController::class, 'show']);
        Route::put('/collections/{collection}', [KnowledgeCollectionController::class, 'update']);
        Route::delete('/collections/{collection}', [KnowledgeCollectionController::class, 'destroy']);
        Route::post('/collections/{collection}/articles', [KnowledgeCollectionController::class, 'attachArticle']);
        Route::delete('/collections/{collection}/articles/{article}', [KnowledgeCollectionController::class, 'detachArticle']);

        Route::get('/business-info', [BusinessInformationController::class, 'index']);
        Route::get('/business-info/grouped', [BusinessInformationController::class, 'grouped']);
        Route::post('/business-info', [BusinessInformationController::class, 'store']);
        Route::get('/business-info/{info}', [BusinessInformationController::class, 'show']);
        Route::put('/business-info/{info}', [BusinessInformationController::class, 'update']);
        Route::delete('/business-info/{info}', [BusinessInformationController::class, 'destroy']);

        Route::get('/articles/{article}/media', [KnowledgeMediaController::class, 'index']);
        Route::post('/articles/{article}/media', [KnowledgeMediaController::class, 'store']);
        Route::delete('/media/{media}', [KnowledgeMediaController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected', 'auth.permission:whatsapp.view'])->prefix('whatsapp')->group(function () {
        Route::get('/account', [WhatsAppAccountController::class, 'show']);
        Route::get('/account/status', [WhatsAppAccountController::class, 'status']);
        Route::get('/account/info', [WhatsAppAccountController::class, 'info']);
        Route::get('/diagnostics', [WhatsAppAccountController::class, 'diagnostics']);

        Route::middleware('auth.permission:whatsapp.phone.manage')->prefix('account')->group(function () {
            Route::post('/connect', [WhatsAppAccountController::class, 'connect']);
            Route::post('/disconnect', [WhatsAppAccountController::class, 'disconnect']);
            Route::post('/reconnect', [WhatsAppAccountController::class, 'reconnect']);
            Route::post('/sync', [WhatsAppAccountController::class, 'sync']);
            Route::post('/verify', [WhatsAppAccountController::class, 'verify']);
        });

        Route::get('/messages', [MessageController::class, 'index']);
        Route::get('/messages/stats', [MessageController::class, 'stats']);
        Route::get('/messages/conversation/{phone}', [MessageController::class, 'conversation']);

        Route::middleware('auth.permission:whatsapp.send')->group(function () {
            Route::post('/messages/send', [MessageController::class, 'send']);
            Route::post('/media', [WhatsAppMediaController::class, 'upload']);
        });

        Route::get('/messages/{message}', [MessageController::class, 'show']);

        Route::get('/media', [WhatsAppMediaController::class, 'index']);
        Route::get('/media/{media}', [WhatsAppMediaController::class, 'show']);

        Route::get('/media/{media}/download', [WhatsAppMediaController::class, 'download']);
        Route::delete('/media/{media}', [WhatsAppMediaController::class, 'destroy']);

        Route::get('/webhook/health', [WebhookController::class, 'health']);
        Route::get('/webhook/events', [WebhookController::class, 'events']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('conversations')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'store']);
        Route::get('/stats', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'stats']);
        Route::get('/{conversation}', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'show']);
        Route::put('/{conversation}', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'update']);
        Route::delete('/{conversation}', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'destroy']);
        Route::post('/{conversation}/assign', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'assign']);
        Route::post('/{conversation}/close', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'close']);
        Route::post('/{conversation}/reopen', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'reopen']);
        Route::post('/{conversation}/labels', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'attachLabel']);
        Route::delete('/{conversation}/labels/{label}', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'detachLabel']);
        Route::get('/{conversation}/events', [\App\Http\Controllers\Api\V1\Conversation\ConversationController::class, 'events']);
        Route::get('/{conversation}/messages', [\App\Http\Controllers\Api\V1\Conversation\ConversationMessageController::class, 'index']);
        Route::post('/{conversation}/messages', [\App\Http\Controllers\Api\V1\Conversation\ConversationMessageController::class, 'store']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('inbox')->group(function () {
        Route::get('/conversations', [InboxController::class, 'index']);
        Route::get('/conversations/{conversation}', [InboxController::class, 'show']);
        Route::post('/conversations/{conversation}/assign', [InboxController::class, 'assign']);
        Route::post('/conversations/{conversation}/takeover', [InboxController::class, 'takeover']);
        Route::post('/conversations/{conversation}/release', [InboxController::class, 'release']);
        Route::patch('/conversations/{conversation}/status', [InboxController::class, 'status']);
        Route::patch('/conversations/{conversation}/pin', [InboxController::class, 'pin']);
        Route::post('/conversations/{conversation}/notes', [InboxController::class, 'note']);
        Route::post('/conversations/{conversation}/draft', [InboxController::class, 'draft']);
        Route::delete('/conversations/{conversation}/draft', [InboxController::class, 'deleteDraft']);
        Route::post('/conversations/{conversation}/typing', [InboxController::class, 'typing']);
        Route::post('/conversations/{conversation}/read', [InboxController::class, 'read']);
        Route::post('/conversations/{conversation}/suggestions', [InboxController::class, 'suggestions']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('conversation-labels')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Conversation\ConversationLabelController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Conversation\ConversationLabelController::class, 'store']);
        Route::put('/{conversationLabel}', [\App\Http\Controllers\Api\V1\Conversation\ConversationLabelController::class, 'update']);
        Route::delete('/{conversationLabel}', [\App\Http\Controllers\Api\V1\Conversation\ConversationLabelController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('ai')->group(function () {
        Route::get('/health', [\App\Http\Controllers\Api\V1\AI\AIHealthController::class, 'index']);
        Route::get('/models', [\App\Http\Controllers\Api\V1\AI\AIHealthController::class, 'models']);
        Route::get('/providers/available', [\App\Http\Controllers\Api\V1\AI\AIProviderController::class, 'available']);

        Route::middleware('auth.permission:ai.chat')->group(function () {
            Route::post('/generate', [\App\Http\Controllers\Api\V1\AI\AIRequestController::class, 'generate']);
            Route::get('/requests', [\App\Http\Controllers\Api\V1\AI\AIRequestController::class, 'history']);
            Route::get('/requests/{id}', [\App\Http\Controllers\Api\V1\AI\AIRequestController::class, 'show']);
        });

        Route::middleware('auth.permission:ai.configure')->group(function () {
            Route::get('/configuration', [\App\Http\Controllers\Api\V1\AI\AIConfigurationController::class, 'show']);
            Route::put('/configuration', [\App\Http\Controllers\Api\V1\AI\AIConfigurationController::class, 'update']);
            Route::get('/configuration/resolved', [\App\Http\Controllers\Api\V1\AI\AIConfigurationController::class, 'resolved']);
        });

        Route::middleware('auth.permission:ai.view_usage')->group(function () {
            Route::get('/usage', [\App\Http\Controllers\Api\V1\AI\AIUsageController::class, 'index']);
            Route::get('/usage/totals', [\App\Http\Controllers\Api\V1\AI\AIUsageController::class, 'totals']);
            Route::get('/usage/by-provider', [\App\Http\Controllers\Api\V1\AI\AIUsageController::class, 'byProvider']);
        });
    });

    Route::middleware(['auth:sanctum', 'auth.permission:ai.manage_providers'])->prefix('ai')->group(function () {
        // Admin-only provider management
        Route::apiResource('providers', \App\Http\Controllers\Api\V1\AI\AIProviderController::class);
        Route::post('/providers/{aiProvider}/sync-models', [\App\Http\Controllers\Api\V1\AI\AIProviderController::class, 'syncModels']);
        Route::get('/providers/{aiProvider}/health', [\App\Http\Controllers\Api\V1\AI\AIProviderController::class, 'health']);
        Route::get('/providers/{aiProvider}/health-history', [\App\Http\Controllers\Api\V1\AI\AIProviderController::class, 'healthHistory']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected', 'auth.permission:ai.manage_prompts'])->prefix('prompts')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\AI\PromptTemplateController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V1\AI\PromptTemplateController::class, 'store']);
        Route::get('/{promptTemplate}', [\App\Http\Controllers\Api\V1\AI\PromptTemplateController::class, 'show']);
        Route::put('/{promptTemplate}', [\App\Http\Controllers\Api\V1\AI\PromptTemplateController::class, 'update']);
        Route::delete('/{promptTemplate}', [\App\Http\Controllers\Api\V1\AI\PromptTemplateController::class, 'destroy']);
        Route::post('/{promptTemplate}/test', [\App\Http\Controllers\Api\V1\AI\PromptTemplateController::class, 'test']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('rag')->group(function () {
        Route::post('/search', [\App\Http\Controllers\Api\V1\RAG\RetrievalController::class, 'search']);
        Route::post('/context', [\App\Http\Controllers\Api\V1\RAG\RetrievalController::class, 'contextPreview']);
        Route::post('/analyze', [\App\Http\Controllers\Api\V1\RAG\RetrievalController::class, 'analyzeQuery']);
        Route::get('/strategies', [\App\Http\Controllers\Api\V1\RAG\RetrievalController::class, 'strategies']);
        Route::post('/prompt-preview', [\App\Http\Controllers\Api\V1\RAG\DiagnosticsController::class, 'promptPreview']);
        Route::post('/ranking-preview', [\App\Http\Controllers\Api\V1\RAG\DiagnosticsController::class, 'rankingPreview']);
        Route::get('/system-info', [\App\Http\Controllers\Api\V1\RAG\DiagnosticsController::class, 'systemInfo']);

        Route::get('/chunks', [\App\Http\Controllers\Api\V1\RAG\ChunkController::class, 'index']);
        Route::get('/chunks/stats', [\App\Http\Controllers\Api\V1\RAG\ChunkController::class, 'stats']);
        Route::get('/chunks/by-source/{sourceType}/{sourceId?}', [\App\Http\Controllers\Api\V1\RAG\ChunkController::class, 'bySource']);
        Route::get('/chunks/{id}', [\App\Http\Controllers\Api\V1\RAG\ChunkController::class, 'show']);
        Route::post('/chunks/preview', [\App\Http\Controllers\Api\V1\RAG\ChunkController::class, 'previewChunking']);

        Route::get('/logs', [\App\Http\Controllers\Api\V1\RAG\DiagnosticsController::class, 'logs']);
        Route::get('/logs/{id}', [\App\Http\Controllers\Api\V1\RAG\DiagnosticsController::class, 'logDetail']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('workflows')->group(function () {
        Route::get('/', [WorkflowController::class, 'index']);
        Route::post('/', [WorkflowController::class, 'store']);
        Route::post('/import', [WorkflowController::class, 'import']);
        Route::get('/runs/{run}', [WorkflowController::class, 'runShow']);
        Route::post('/runs/{run}/retry', [WorkflowController::class, 'runRetry']);
        Route::post('/runs/{run}/cancel', [WorkflowController::class, 'runCancel']);
        Route::get('/{workflow}', [WorkflowController::class, 'show']);
        Route::put('/{workflow}', [WorkflowController::class, 'update']);
        Route::delete('/{workflow}', [WorkflowController::class, 'destroy']);
        Route::post('/{workflow}/enable', [WorkflowController::class, 'enable']);
        Route::post('/{workflow}/disable', [WorkflowController::class, 'disable']);
        Route::post('/{workflow}/execute', [WorkflowController::class, 'execute']);
        Route::get('/{workflow}/export', [WorkflowController::class, 'export']);
        Route::get('/{workflow}/versions', [WorkflowController::class, 'versions']);
        Route::get('/{workflow}/runs', [WorkflowController::class, 'runs']);
        Route::get('/{workflow}/logs', [WorkflowController::class, 'logs']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('broadcast')->group(function () {
        Route::post('/audience/preview', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'previewAudience']);
        Route::post('/audience/count', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'countAudience']);

        Route::get('/campaigns', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'index']);
        Route::post('/campaigns', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'store']);
        Route::get('/campaigns/{campaign}', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'show']);
        Route::put('/campaigns/{campaign}', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'update']);
        Route::delete('/campaigns/{campaign}', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'destroy']);
        Route::post('/campaigns/{campaign}/duplicate', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'duplicate']);
        Route::post('/campaigns/{campaign}/schedule', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'schedule']);
        Route::post('/campaigns/{campaign}/pause', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'pause']);
        Route::post('/campaigns/{campaign}/resume', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'resume']);
        Route::post('/campaigns/{campaign}/cancel', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'cancel']);
        Route::post('/campaigns/{campaign}/archive', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'archive']);
        Route::get('/campaigns/{campaign}/stats', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'stats']);
        Route::get('/campaigns/{campaign}/recipients', [\App\Http\Controllers\Api\V1\Broadcast\CampaignController::class, 'recipients']);

        Route::get('/templates', [\App\Http\Controllers\Api\V1\Broadcast\TemplateController::class, 'index']);
        Route::post('/templates', [\App\Http\Controllers\Api\V1\Broadcast\TemplateController::class, 'store']);
        Route::get('/templates/active', [\App\Http\Controllers\Api\V1\Broadcast\TemplateController::class, 'active']);
        Route::post('/templates/sync', [\App\Http\Controllers\Api\V1\Broadcast\TemplateController::class, 'sync']);
        Route::get('/templates/{template}', [\App\Http\Controllers\Api\V1\Broadcast\TemplateController::class, 'show']);
        Route::put('/templates/{template}', [\App\Http\Controllers\Api\V1\Broadcast\TemplateController::class, 'update']);
        Route::delete('/templates/{template}', [\App\Http\Controllers\Api\V1\Broadcast\TemplateController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'tenant', 'business.selected'])->prefix('analytics')->group(function () {
        Route::get('/overview', [\App\Http\Controllers\Api\V1\Analytics\AnalyticsController::class, 'overview']);
        Route::get('/conversations', [\App\Http\Controllers\Api\V1\Analytics\AnalyticsController::class, 'conversations']);
        Route::get('/customers', [\App\Http\Controllers\Api\V1\Analytics\AnalyticsController::class, 'customers']);
        Route::get('/ai', [\App\Http\Controllers\Api\V1\Analytics\AnalyticsController::class, 'ai']);
        Route::get('/agents', [\App\Http\Controllers\Api\V1\Analytics\AnalyticsController::class, 'agents']);
        Route::get('/campaigns', [\App\Http\Controllers\Api\V1\Analytics\AnalyticsController::class, 'campaigns']);
        Route::get('/workflows', [\App\Http\Controllers\Api\V1\Analytics\AnalyticsController::class, 'workflows']);
        Route::get('/knowledge', [\App\Http\Controllers\Api\V1\Analytics\AnalyticsController::class, 'knowledge']);
    });

    Route::middleware(['auth:sanctum', 'throttle:authorization'])->prefix('authorization')->group(function () {
        Route::get('/me', [AuthorizationController::class, 'me']);
        Route::get('/registry', [AuthorizationController::class, 'registry']);

        Route::get('/permissions', [PermissionController::class, 'index']);
        Route::get('/permissions/{id}', [PermissionController::class, 'show']);
        Route::post('/permissions/sync', [PermissionController::class, 'sync']);
        Route::get('/permissions/groups', [PermissionController::class, 'groups']);
        Route::get('/permissions/registry', [PermissionController::class, 'registry']);

        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{id}', [RoleController::class, 'show']);
        Route::get('/roles/hierarchy', [RoleController::class, 'hierarchy']);
        Route::post('/roles/assign', [RoleController::class, 'assignToUser']);
        Route::post('/roles/remove', [RoleController::class, 'removeFromUser']);
        Route::get('/roles/user/{userId}', [RoleController::class, 'userRoles']);
    });
});
