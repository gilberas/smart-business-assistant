<?php

namespace App\Providers;

use App\Contracts\AI\AIServiceInterface;
use App\Contracts\AI\PromptServiceInterface;
use App\Contracts\AuthorizationServiceInterface;
use App\Contracts\Workflow\ActionHandlerInterface;
use App\Contracts\Workflow\ConditionEvaluatorInterface;
use App\Contracts\Workflow\TriggerDispatcherInterface;
use App\Contracts\Workflow\WorkflowEngineInterface;
use App\Contracts\Workflow\WorkflowServiceInterface;
use App\Listeners\Workflow\WorkflowEventSubscriber;
use App\Managers\TenantManager;
use App\Repositories\Workflow\WorkflowRepository;
use App\Observers\CustomerObserver;
use App\Models\Customer;
use App\Repositories\Customer\CustomerActivityRepository;
use App\Repositories\WhatsApp\DeliveryLogRepository;
use App\Repositories\WhatsApp\MediaRepository;
use App\Repositories\WhatsApp\MessageRepository;
use App\Repositories\WhatsApp\MessageStatusRepository;
use App\Repositories\WhatsApp\PhoneNumberRepository;
use App\Repositories\WhatsApp\WebhookEventRepository;
use App\Repositories\WhatsApp\WhatsAppAccountRepository;
use App\Services\TenantService;
use App\Services\WhatsApp\MediaService;
use App\Services\WhatsApp\SandboxService;
use App\Services\WhatsApp\WebhookService;
use App\Services\WhatsApp\WhatsAppApiClient;
use App\Services\WhatsApp\WhatsAppService;
use App\Repositories\Customer\CustomerNoteRepository;
use App\Repositories\Customer\CustomerRepository;
use App\Repositories\Customer\CustomerTagRepository;
use App\Repositories\Knowledge\BusinessInformationRepository;
use App\Repositories\Knowledge\KnowledgeArticleRepository;
use App\Repositories\Knowledge\KnowledgeCategoryRepository;
use App\Repositories\Knowledge\KnowledgeCollectionRepository;
use App\Repositories\Knowledge\KnowledgeFaqRepository;
use App\Repositories\Knowledge\KnowledgeMediaRepository;
use App\Repositories\Knowledge\KnowledgeTagRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\Team\InvitationRepository;
use App\Repositories\Team\MembershipRepository;
use App\Services\AuthorizationService;
use App\Services\Customer\CustomerActivityService;
use App\Services\Customer\CustomerExportService;
use App\Services\Customer\CustomerImportService;
use App\Services\Customer\CustomerNoteService;
use App\Services\Customer\CustomerService;
use App\Services\Customer\CustomerTagService;
use App\Services\Knowledge\BusinessInformationService;
use App\Services\Knowledge\KnowledgeArticleService;
use App\Services\Knowledge\KnowledgeCategoryService;
use App\Services\Knowledge\KnowledgeCollectionService;
use App\Services\Knowledge\KnowledgeFaqService;
use App\Services\Knowledge\KnowledgeMediaService;
use App\Services\Knowledge\KnowledgeTagService;
use App\Services\PermissionService;
use App\Services\RoleService;
use App\Services\Team\InvitationService;
use App\Services\Team\MembershipService;
use App\Services\Team\TeamService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class, function ($app) {
            return new TenantManager($app['session']);
        });

        $this->app->singleton(AuthorizationServiceInterface::class, function ($app) {
            return new AuthorizationService($app->make(TenantManager::class));
        });

        $this->app->singleton(PermissionService::class, function ($app) {
            return new PermissionService($app->make(PermissionRepository::class));
        });

        $this->app->singleton(RoleService::class, function ($app) {
            return new RoleService(
                $app->make(RoleRepository::class),
                $app->make(AuthorizationServiceInterface::class),
            );
        });

        $this->app->singleton(InvitationRepository::class);
        $this->app->singleton(MembershipRepository::class);

        $this->app->singleton(MembershipService::class, function ($app) {
            return new MembershipService(
                $app->make(MembershipRepository::class),
                $app->make(AuthorizationServiceInterface::class),
            );
        });

        $this->app->singleton(InvitationService::class, function ($app) {
            return new InvitationService(
                $app->make(InvitationRepository::class),
                $app->make(MembershipService::class),
            );
        });

        $this->app->singleton(TeamService::class, function ($app) {
            return new TeamService(
                $app->make(InvitationService::class),
                $app->make(MembershipService::class),
            );
        });

        $this->app->singleton(CustomerRepository::class);
        $this->app->singleton(CustomerTagRepository::class);
        $this->app->singleton(CustomerNoteRepository::class);
        $this->app->singleton(CustomerActivityRepository::class);

        $this->app->singleton(CustomerTagService::class, function ($app) {
            return new CustomerTagService($app->make(CustomerTagRepository::class));
        });

        $this->app->singleton(CustomerNoteService::class, function ($app) {
            return new CustomerNoteService($app->make(CustomerNoteRepository::class));
        });

        $this->app->singleton(CustomerActivityService::class, function ($app) {
            return new CustomerActivityService($app->make(CustomerActivityRepository::class));
        });

        $this->app->singleton(CustomerImportService::class, function ($app) {
            return new CustomerImportService(
                $app->make(CustomerRepository::class),
                $app->make(CustomerTagRepository::class),
            );
        });

        $this->app->singleton(CustomerExportService::class);

        $this->app->singleton(CustomerService::class, function ($app) {
            return new CustomerService(
                $app->make(CustomerRepository::class),
                $app->make(CustomerTagRepository::class),
            );
        });

        $this->app->singleton(KnowledgeArticleRepository::class);
        $this->app->singleton(KnowledgeCategoryRepository::class);
        $this->app->singleton(KnowledgeTagRepository::class);
        $this->app->singleton(KnowledgeFaqRepository::class);
        $this->app->singleton(KnowledgeCollectionRepository::class);
        $this->app->singleton(BusinessInformationRepository::class);
        $this->app->singleton(KnowledgeMediaRepository::class);

        $this->app->singleton(KnowledgeTagService::class, function ($app) {
            return new KnowledgeTagService($app->make(KnowledgeTagRepository::class));
        });

        $this->app->singleton(KnowledgeFaqService::class, function ($app) {
            return new KnowledgeFaqService($app->make(KnowledgeFaqRepository::class));
        });

        $this->app->singleton(KnowledgeCollectionService::class, function ($app) {
            return new KnowledgeCollectionService($app->make(KnowledgeCollectionRepository::class));
        });

        $this->app->singleton(BusinessInformationService::class, function ($app) {
            return new BusinessInformationService($app->make(BusinessInformationRepository::class));
        });

        $this->app->singleton(KnowledgeMediaService::class, function ($app) {
            return new KnowledgeMediaService($app->make(KnowledgeMediaRepository::class));
        });

        $this->app->singleton(KnowledgeCategoryService::class, function ($app) {
            return new KnowledgeCategoryService($app->make(KnowledgeCategoryRepository::class));
        });

        $this->app->singleton(KnowledgeArticleService::class, function ($app) {
            return new KnowledgeArticleService(
                $app->make(KnowledgeArticleRepository::class),
                $app->make(KnowledgeTagRepository::class),
            );
        });

        $this->app->singleton(WhatsAppAccountRepository::class);
        $this->app->singleton(PhoneNumberRepository::class);
        $this->app->singleton(WebhookEventRepository::class);
        $this->app->singleton(MessageRepository::class);
        $this->app->singleton(MessageStatusRepository::class);
        $this->app->singleton(MediaRepository::class);
        $this->app->singleton(DeliveryLogRepository::class);

        $this->app->singleton(WhatsAppApiClient::class);

        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService(
                $app->make(WhatsAppAccountRepository::class),
                $app->make(PhoneNumberRepository::class),
                $app->make(MessageRepository::class),
                $app->make(WhatsAppApiClient::class),
                $app->make(TenantService::class),
            );
        });

        $this->app->singleton(WebhookService::class, function ($app) {
            return new WebhookService(
                $app->make(WebhookEventRepository::class),
                $app->make(WhatsAppAccountRepository::class),
            );
        });

        $this->app->singleton(MediaService::class, function ($app) {
            return new MediaService(
                $app->make(MediaRepository::class),
                $app->make(WhatsAppAccountRepository::class),
                $app->make(WhatsAppApiClient::class),
                $app->make(WhatsAppService::class),
            );
        });

        $this->app->singleton(SandboxService::class, function ($app) {
            return new SandboxService($app->make(WhatsAppService::class));
        });

        $this->app->singleton(\App\Repositories\Conversation\ConversationRepository::class);
        $this->app->singleton(\App\Repositories\Conversation\ConversationLabelRepository::class);
        $this->app->singleton(\App\Repositories\Conversation\ConversationEventRepository::class);
        $this->app->singleton(\App\Repositories\Conversation\MessageRepository::class);
        $this->app->singleton(\App\Repositories\Conversation\ConversationDraftRepository::class);
        $this->app->singleton(\App\Repositories\Conversation\RoutingHistoryRepository::class);

        $this->app->singleton(\App\Services\Conversation\ConversationEventService::class, function ($app) {
            return new \App\Services\Conversation\ConversationEventService(
                $app->make(\App\Repositories\Conversation\ConversationEventRepository::class),
            );
        });

        $this->app->singleton(\App\Services\Conversation\ConversationService::class, function ($app) {
            return new \App\Services\Conversation\ConversationService(
                $app->make(\App\Repositories\Conversation\ConversationRepository::class),
                $app->make(\App\Repositories\Conversation\ConversationEventRepository::class),
                $app->make(\App\Repositories\Conversation\ConversationLabelRepository::class),
            );
        });

        $this->app->singleton(\App\Services\Conversation\MessageService::class, function ($app) {
            return new \App\Services\Conversation\MessageService(
                $app->make(\App\Repositories\Conversation\MessageRepository::class),
                $app->make(\App\Repositories\Conversation\ConversationRepository::class),
            );
        });

        $this->app->singleton(\App\Services\Conversation\RoutingService::class, function ($app) {
            return new \App\Services\Conversation\RoutingService(
                $app->make(\App\Repositories\Conversation\ConversationRepository::class),
                $app->make(\App\Repositories\Conversation\RoutingHistoryRepository::class),
            );
        });

        $this->app->singleton(\App\Services\Inbox\InboxDecisionEngineService::class);

        $this->app->singleton(\App\Services\Inbox\InboxSuggestionService::class, function ($app) {
            return new \App\Services\Inbox\InboxSuggestionService(
                $app->make(\App\Services\RAG\RetrievalService::class),
                $app->make(\App\Services\AI\AIService::class),
                $app->make(\App\Services\Conversation\MessageService::class),
                $app->make(\App\Services\Inbox\InboxDecisionEngineService::class),
            );
        });

        $this->app->singleton(\App\Services\Inbox\InboxService::class, function ($app) {
            return new \App\Services\Inbox\InboxService(
                $app->make(\App\Repositories\Conversation\ConversationRepository::class),
                $app->make(\App\Services\Conversation\ConversationService::class),
                $app->make(\App\Repositories\Conversation\ConversationEventRepository::class),
                $app->make(\App\Services\Conversation\ConversationEventService::class),
                $app->make(\App\Repositories\Conversation\ConversationDraftRepository::class),
                $app->make(\App\Services\Conversation\MessageService::class),
                $app->make(\App\Services\RAG\RetrievalService::class),
                $app->make(\App\Services\Inbox\InboxSuggestionService::class),
            );
        });

        // AI Infrastructure
        $this->app->singleton(\App\Repositories\AI\AIProviderRepository::class);
        $this->app->singleton(\App\Repositories\AI\AIModelRepository::class);
        $this->app->singleton(\App\Repositories\AI\AIConfigurationRepository::class);
        $this->app->singleton(\App\Repositories\AI\PromptTemplateRepository::class);
        $this->app->singleton(\App\Repositories\AI\PromptVersionRepository::class);
        $this->app->singleton(\App\Repositories\AI\AIRequestRepository::class);
        $this->app->singleton(\App\Repositories\AI\AIUsageRepository::class);
        $this->app->singleton(\App\Repositories\AI\ProviderHealthRepository::class);

        $this->app->singleton(\App\Services\AI\AIProviderFactory::class, function ($app) {
            return new \App\Services\AI\AIProviderFactory(
                $app->make(\App\Repositories\AI\AIProviderRepository::class),
            );
        });

        $this->app->singleton(AIServiceInterface::class, \App\Services\AI\AIService::class);

        $this->app->singleton(\App\Services\AI\AIService::class, function ($app) {
            return new \App\Services\AI\AIService(
                $app->make(\App\Services\AI\AIProviderFactory::class),
                $app->make(\App\Repositories\AI\AIRequestRepository::class),
                $app->make(\App\Repositories\AI\AIUsageRepository::class),
                $app->make(\App\Services\AI\AIConfigurationService::class),
                $app->make(\App\Services\AI\TokenService::class),
            );
        });

        $this->app->singleton(\App\Services\AI\AIConfigurationService::class, function ($app) {
            return new \App\Services\AI\AIConfigurationService(
                $app->make(\App\Repositories\AI\AIConfigurationRepository::class),
            );
        });

        $this->app->singleton(\App\Services\AI\TokenService::class);

        $this->app->singleton(\App\Services\AI\UsageService::class, function ($app) {
            return new \App\Services\AI\UsageService(
                $app->make(\App\Repositories\AI\AIUsageRepository::class),
            );
        });

        $this->app->singleton(PromptServiceInterface::class, \App\Services\AI\PromptService::class);

        $this->app->singleton(\App\Services\AI\PromptService::class, function ($app) {
            return new \App\Services\AI\PromptService(
                $app->make(\App\Repositories\AI\PromptTemplateRepository::class),
                $app->make(\App\Repositories\AI\PromptVersionRepository::class),
            );
        });

        $this->app->singleton(\App\Services\AI\HealthService::class, function ($app) {
            return new \App\Services\AI\HealthService(
                $app->make(\App\Repositories\AI\AIProviderRepository::class),
                $app->make(\App\Repositories\AI\ProviderHealthRepository::class),
                $app->make(\App\Services\AI\AIProviderFactory::class),
            );
        });

        // RAG Infrastructure
        $this->app->singleton(\App\Repositories\RAG\KnowledgeChunkRepository::class);
        $this->app->singleton(\App\Repositories\RAG\ChunkMetadataRepository::class);
        $this->app->singleton(\App\Repositories\RAG\RetrievalLogRepository::class);
        $this->app->singleton(\App\Repositories\RAG\DocumentIngestionRepository::class);

        $this->app->singleton(\App\Services\RAG\QueryAnalyzerService::class);
        $this->app->singleton(\App\Services\RAG\IntentClassifierService::class);

        $this->app->singleton(\App\Services\RAG\Chunking\ParagraphChunkStrategy::class);
        $this->app->singleton(\App\Services\RAG\Chunking\SectionChunkStrategy::class);
        $this->app->singleton(\App\Services\RAG\Chunking\SlidingWindowChunkStrategy::class);
        $this->app->singleton(\App\Services\RAG\Chunking\FixedSizeChunkStrategy::class);

        $this->app->singleton(\App\Services\RAG\Chunking\ChunkingStrategyFactory::class, function ($app) {
            return new \App\Services\RAG\Chunking\ChunkingStrategyFactory(
                $app->make(\App\Services\RAG\Chunking\ParagraphChunkStrategy::class),
                $app->make(\App\Services\RAG\Chunking\SectionChunkStrategy::class),
                $app->make(\App\Services\RAG\Chunking\SlidingWindowChunkStrategy::class),
                $app->make(\App\Services\RAG\Chunking\FixedSizeChunkStrategy::class),
            );
        });

        $this->app->singleton(\App\Services\RAG\ChunkingService::class, function ($app) {
            return new \App\Services\RAG\ChunkingService(
                $app->make(\App\Services\RAG\Chunking\ChunkingStrategyFactory::class),
            );
        });

        $this->app->singleton(\App\Services\RAG\Search\KeywordSearchStrategy::class);
        $this->app->singleton(\App\Services\RAG\Search\FulltextSearchStrategy::class);
        $this->app->singleton(\App\Services\RAG\Search\TagSearchStrategy::class);
        $this->app->singleton(\App\Services\RAG\Search\CategorySearchStrategy::class);
        $this->app->singleton(\App\Services\RAG\Search\MetadataSearchStrategy::class);

        $this->app->singleton(\App\Services\RAG\Search\HybridSearchStrategy::class, function ($app) {
            return new \App\Services\RAG\Search\HybridSearchStrategy(
                $app->make(\App\Services\RAG\Search\KeywordSearchStrategy::class),
                $app->make(\App\Services\RAG\Search\FulltextSearchStrategy::class),
                $app->make(\App\Services\RAG\Search\TagSearchStrategy::class),
                $app->make(\App\Services\RAG\Search\CategorySearchStrategy::class),
                $app->make(\App\Services\RAG\Search\MetadataSearchStrategy::class),
            );
        });

        $this->app->singleton(\App\Services\RAG\Search\SearchStrategyFactory::class, function ($app) {
            return new \App\Services\RAG\Search\SearchStrategyFactory(
                $app->make(\App\Services\RAG\Search\KeywordSearchStrategy::class),
                $app->make(\App\Services\RAG\Search\FulltextSearchStrategy::class),
                $app->make(\App\Services\RAG\Search\TagSearchStrategy::class),
                $app->make(\App\Services\RAG\Search\CategorySearchStrategy::class),
                $app->make(\App\Services\RAG\Search\MetadataSearchStrategy::class),
                $app->make(\App\Services\RAG\Search\HybridSearchStrategy::class),
            );
        });

        $this->app->singleton(\App\Services\RAG\RankingService::class);
        $this->app->singleton(\App\Services\RAG\SearchService::class, function ($app) {
            return new \App\Services\RAG\SearchService(
                $app->make(\App\Services\RAG\Search\SearchStrategyFactory::class),
                $app->make(\App\Services\RAG\QueryAnalyzerService::class),
                $app->make(\App\Services\RAG\IntentClassifierService::class),
                $app->make(\App\Services\RAG\RankingService::class),
                $app->make(\App\Repositories\RAG\RetrievalLogRepository::class),
            );
        });

        $this->app->singleton(\App\Services\RAG\ContextBuilderService::class);
        $this->app->singleton(\App\Services\RAG\PromptBuilderService::class);

        $this->app->singleton(\App\Services\RAG\RetrievalService::class, function ($app) {
            return new \App\Services\RAG\RetrievalService(
                $app->make(\App\Services\RAG\SearchService::class),
                $app->make(\App\Services\RAG\ContextBuilderService::class),
                $app->make(\App\Services\RAG\PromptBuilderService::class),
                $app->make(\App\Services\RAG\RankingService::class),
            );
        });

        $this->app->singleton(\App\Services\RAG\DocumentProcessingService::class, function ($app) {
            return new \App\Services\RAG\DocumentProcessingService(
                $app->make(\App\Services\RAG\ChunkingService::class),
                $app->make(\App\Services\RAG\Chunking\ChunkingStrategyFactory::class),
                $app->make(\App\Repositories\RAG\KnowledgeChunkRepository::class),
                $app->make(\App\Repositories\RAG\DocumentIngestionRepository::class),
            );
        });

        // Workflow Engine
        $this->app->singleton(WorkflowRepository::class);
        $this->app->singleton(WorkflowServiceInterface::class, \App\Services\Workflow\WorkflowService::class);
        $this->app->singleton(ConditionEvaluatorInterface::class, ConditionEngineService::class);
        $this->app->singleton(ActionHandlerInterface::class, ActionEngineService::class);
        $this->app->singleton(TriggerDispatcherInterface::class, TriggerDispatcherService::class);
        $this->app->singleton(\App\Services\Workflow\WorkflowConditionEngineService::class);
        $this->app->singleton(\App\Services\Workflow\WorkflowActionEngineService::class);
        $this->app->singleton(\App\Services\Workflow\WorkflowExecutionService::class);
        $this->app->singleton(\App\Services\Workflow\WorkflowService::class);
        $this->app->singleton(\App\Services\Workflow\ConditionEngineService::class);
        $this->app->singleton(\App\Services\Workflow\ActionEngineService::class);
        $this->app->singleton(\App\Services\Workflow\TriggerDispatcherService::class);

        // Broadcast / Campaign Engine
        $this->app->singleton(\App\Repositories\Broadcast\CampaignRepository::class);
        $this->app->singleton(\App\Repositories\Broadcast\TemplateRepository::class);
        $this->app->singleton(\App\Repositories\Broadcast\BroadcastRecipientRepository::class);

        $this->app->singleton(\App\Services\Broadcast\AudienceService::class, function ($app) {
            return new \App\Services\Broadcast\AudienceService(
                $app->make(\App\Repositories\Broadcast\BroadcastRecipientRepository::class),
            );
        });

        $this->app->singleton(\App\Services\Broadcast\DeliveryService::class, function ($app) {
            return new \App\Services\Broadcast\DeliveryService(
                $app->make(\App\Repositories\Broadcast\BroadcastRecipientRepository::class),
                $app->make(\App\Services\WhatsApp\WhatsAppService::class),
            );
        });

        $this->app->singleton(\App\Services\Broadcast\TemplateService::class, function ($app) {
            return new \App\Services\Broadcast\TemplateService(
                $app->make(\App\Repositories\Broadcast\TemplateRepository::class),
                $app->make(\App\Services\WhatsApp\WhatsAppService::class),
            );
        });

        $this->app->singleton(\App\Services\Broadcast\CampaignService::class, function ($app) {
            return new \App\Services\Broadcast\CampaignService(
                $app->make(\App\Repositories\Broadcast\CampaignRepository::class),
                $app->make(\App\Repositories\Broadcast\BroadcastRecipientRepository::class),
                $app->make(\App\Repositories\Broadcast\TemplateRepository::class),
                $app->make(\App\Services\Broadcast\AudienceService::class),
            );
        });

        $this->app->singleton(\App\Services\Broadcast\SchedulingService::class, function ($app) {
            return new \App\Services\Broadcast\SchedulingService(
                $app->make(\App\Repositories\Broadcast\CampaignRepository::class),
                $app->make(\App\Repositories\Broadcast\BroadcastRecipientRepository::class),
                $app->make(\App\Services\Broadcast\CampaignService::class),
            );
        });

        // Analytics
        $this->app->singleton(\App\Services\Analytics\Calculators\OverviewCalculator::class);
        $this->app->singleton(\App\Services\Analytics\Calculators\ConversationCalculator::class);
        $this->app->singleton(\App\Services\Analytics\Calculators\CustomerCalculator::class);
        $this->app->singleton(\App\Services\Analytics\Calculators\AICalculator::class);
        $this->app->singleton(\App\Services\Analytics\Calculators\AgentCalculator::class);
        $this->app->singleton(\App\Services\Analytics\Calculators\CampaignCalculator::class);
        $this->app->singleton(\App\Services\Analytics\Calculators\WorkflowCalculator::class);
        $this->app->singleton(\App\Services\Analytics\Calculators\KnowledgeCalculator::class);

        $this->app->singleton(\App\Services\Analytics\AnalyticsService::class, function ($app) {
            return new \App\Services\Analytics\AnalyticsService(
                $app->make(\App\Services\Analytics\Calculators\OverviewCalculator::class),
                $app->make(\App\Services\Analytics\Calculators\ConversationCalculator::class),
                $app->make(\App\Services\Analytics\Calculators\CustomerCalculator::class),
                $app->make(\App\Services\Analytics\Calculators\AICalculator::class),
                $app->make(\App\Services\Analytics\Calculators\AgentCalculator::class),
                $app->make(\App\Services\Analytics\Calculators\CampaignCalculator::class),
                $app->make(\App\Services\Analytics\Calculators\WorkflowCalculator::class),
                $app->make(\App\Services\Analytics\Calculators\KnowledgeCalculator::class),
            );
        });
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerObservers();
        $this->registerEventSubscribers();
    }

    protected function registerEventSubscribers(): void
    {
        $this->app->make('events')->subscribe(WorkflowEventSubscriber::class);
        $this->app->make('events')->subscribe(\App\Listeners\Broadcast\BroadcastEventSubscriber::class);
    }

    protected function registerObservers(): void
    {
        Customer::observe(CustomerObserver::class);
    }

    protected function configureDefaults(): void
    {
        Schema::defaultStringLength(191);
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
