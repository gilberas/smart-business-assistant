<?php

use App\Enums\AI\AIProvider;
use App\Enums\AI\AIRequestStatus;
use App\Enums\AI\AIModelCapability;
use App\Enums\AI\PromptType;
use App\Models\AI\AIProvider as AIProviderModel;
use App\Models\AI\AIModel;
use App\Models\AI\AIConfiguration;
use App\Models\AI\AIRequest;
use App\Models\AI\AIUsage;
use App\Models\Business;
use App\Models\PromptTemplate;
use App\Models\User;
use App\DTOs\AI\MessageDTO;
use App\DTOs\AI\UsageDTO;
use App\DTOs\AI\AIResponseDTO;
use App\DTOs\AI\AIRequestDTO;
use App\Services\AI\TokenService;
use App\Services\AI\PromptService;
use App\Services\AI\AIConfigurationService;
use App\Services\AI\UsageService;
use App\Services\AI\AIProviderFactory;
use App\Support\RoleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Enum Tests ─────────────────────────────────────────────────────────

describe('AI Enums', function () {
    it('AIProvider has correct cases', function () {
        expect(AIProvider::OpenAI->value)->toBe('openai');
        expect(AIProvider::Anthropic->value)->toBe('anthropic');
        expect(AIProvider::Google->value)->toBe('google');
        expect(AIProvider::OpenRouter->value)->toBe('openrouter');
    });

    it('AIProvider returns labels', function () {
        expect(AIProvider::OpenAI->label())->toBe('OpenAI');
        expect(AIProvider::Anthropic->label())->toBe('Anthropic Claude');
    });

    it('AIProvider returns default models', function () {
        expect(AIProvider::OpenAI->defaultModel())->toBe('gpt-4o');
        expect(AIProvider::Google->defaultModel())->toBe('gemini-2.0-flash');
    });

    it('AIRequestStatus has terminal states', function () {
        expect(AIRequestStatus::Completed->isTerminal())->toBeTrue();
        expect(AIRequestStatus::Failed->isTerminal())->toBeTrue();
        expect(AIRequestStatus::Processing->isTerminal())->toBeFalse();
    });

    it('AIModelCapability has correct values', function () {
        expect(AIModelCapability::Text->value)->toBe('text');
        expect(AIModelCapability::StructuredOutput->value)->toBe('structured_output');
    });

    it('PromptType has correct cases', function () {
        expect(PromptType::System->value)->toBe('system');
        expect(PromptType::Classification->value)->toBe('classification');
    });
});

// ─── DTO Tests ──────────────────────────────────────────────────────────

describe('AI DTOs', function () {
    it('MessageDTO creates valid messages', function () {
        $msg = MessageDTO::user('Hello');
        expect($msg->role)->toBe('user');
        expect($msg->content)->toBe('Hello');
        expect($msg->toArray())->toBe(['role' => 'user', 'content' => 'Hello']);
    });

    it('UsageDTO calculates totals', function () {
        $usage = UsageDTO::fromArray(['prompt_tokens' => 100, 'completion_tokens' => 50]);
        expect($usage->totalTokens)->toBe(150);
    });

    it('AIResponseDTO can be success or error', function () {
        $success = AIResponseDTO::success('Response text', null, 100);
        expect($success->isSuccess())->toBeTrue();
        expect($success->content)->toBe('Response text');

        $error = AIResponseDTO::error('Something broke');
        expect($error->isError())->toBeTrue();
        expect($error->content)->toBe('');
    });
});

// ─── Service Tests ──────────────────────────────────────────────────────

describe('TokenService', function () {
    it('counts tokens approximately', function () {
        $service = app(TokenService::class);
        $count = $service->countText('Hello world, this is a test message with some content');
        expect($count)->toBeGreaterThan(0);
    });

    it('estimates cost based on model pricing', function () {
        $service = app(TokenService::class);
        $cost = $service->estimateCost(1000, 500, 'gpt-4o');
        expect($cost)->toBeGreaterThan(0);
    });

    it('counts tokens from message array', function () {
        $service = app(TokenService::class);
        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'What is the weather today?'],
        ];
        $count = $service->count($messages);
        expect($count)->toBeGreaterThan(0);
    });
});

describe('PromptService', function () {
    it('compiles template with variables', function () {
        $service = app(PromptService::class);
        $result = $service->compile('Hello {{name}}, welcome to {{business}}!', [
            'name' => 'John',
            'business' => 'Acme Corp',
        ]);
        expect($result)->toBe('Hello John, welcome to Acme Corp!');
    });

    it('extracts variables from template', function () {
        $service = app(PromptService::class);
        $vars = $service->getVariables('Hello {{name}}, your {{role}} is active');
        expect($vars)->toContain('name');
        expect($vars)->toContain('role');
    });

    it('creates version when updating template', function () {
        $business = Business::factory()->create();
        $template = PromptTemplate::factory()->create([
            'business_id' => $business->id,
            'content' => 'Original content',
            'version' => 1,
        ]);

        $service = app(PromptService::class);
        $service->createVersion($template, 'Updated content', 'Changed wording');

        expect($template->fresh()->version)->toBe(2);
        expect($template->fresh()->content)->toBe('Updated content');
    });

    it('handles missing variables gracefully', function () {
        $service = app(PromptService::class);
        $result = $service->compile('{{name}} is {{age}} years old', ['name' => 'Alice']);
        expect($result)->toBe('Alice is  years old');
    });
});

describe('AIConfigurationService', function () {
    it('creates configuration for business', function () {
        $business = Business::factory()->create();
        $service = app(AIConfigurationService::class);

        $config = $service->updateOrCreate($business->id, [
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'temperature' => 0.5,
        ]);

        expect($config->provider)->toBe('openai');
        expect($config->model)->toBe('gpt-4o');
        expect($config->temperature)->toBe(0.5);
    });

    it('retrieves configuration for business', function () {
        $business = Business::factory()->create();
        AIConfiguration::factory()->create([
            'business_id' => $business->id,
            'provider' => 'anthropic',
        ]);

        $service = app(AIConfigurationService::class);
        $config = $service->getForBusiness($business->id);

        expect($config)->not->toBeNull();
        expect($config->provider)->toBe('anthropic');
    });

    it('returns defaults when no configuration exists', function () {
        $service = app(AIConfigurationService::class);
        $resolved = $service->resolve(999);
        expect($resolved['provider'])->toBe('openai');
    });
});

// ─── Provider Adapter Tests ─────────────────────────────────────────────

describe('AIProviderFactory', function () {
    it('returns default provider', function () {
        AIProviderModel::factory()->openai()->create();

        $factory = app(AIProviderFactory::class);
        $adapter = $factory->default();

        expect($adapter)->not->toBeNull();
    });

    it('creates configured provider by enum', function () {
        $factory = app(AIProviderFactory::class);
        $adapter = $factory->make(AIProvider::OpenAI);

        expect($adapter->name())->toBe('OpenAIAdapter');
        expect(method_exists($adapter, 'generateText'))->toBeTrue();
        expect(method_exists($adapter, 'generateStructuredOutput'))->toBeTrue();
        expect(method_exists($adapter, 'streamResponse'))->toBeTrue();
        expect(method_exists($adapter, 'countTokens'))->toBeTrue();
        expect(method_exists($adapter, 'listModels'))->toBeTrue();
        expect(method_exists($adapter, 'health'))->toBeTrue();
    });
});

// ─── Model Tests ────────────────────────────────────────────────────────

describe('AI Models', function () {
    it('creates AIProvider', function () {
        $provider = AIProviderModel::factory()->openai()->create();
        expect($provider->slug)->toBe('openai');
        expect($provider->is_active)->toBeTrue();
    });

    it('creates AIModel with provider relationship', function () {
        $provider = AIProviderModel::factory()->openai()->create();
        $model = AIModel::factory()->gpt4o()->create([
            'ai_provider_id' => $provider->id,
        ]);

        expect($model->provider)->not->toBeNull();
        expect($model->provider->slug)->toBe('openai');
        expect($model->hasCapability('text'))->toBeTrue();
    });

    it('creates AIConfiguration with business', function () {
        $config = AIConfiguration::factory()->create();
        expect($config->business)->not->toBeNull();
        expect($config->provider)->toBe('openai');
    });

    it('creates PromptTemplate with version tracking', function () {
        $business = Business::factory()->create();
        $template = PromptTemplate::factory()->system()->create([
            'business_id' => $business->id,
        ]);

        expect($template->type)->toBe('system');
        expect($template->version)->toBe(1);
    });

    it('creates AIRequest with usage stats', function () {
        $request = AIRequest::factory()->create();
        expect($request->status)->toBe('completed');
        expect($request->total_tokens)->toBeGreaterThan(0);
    });

    it('creates AIUsage for business', function () {
        $usage = AIUsage::factory()->create();
        expect($usage->business)->not->toBeNull();
        expect($usage->total_requests)->toBeGreaterThan(0);
    });
});

// ─── API Tests ──────────────────────────────────────────────────────────

describe('AI API', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->business = Business::factory()->create(['owner_id' => $this->user->id]);
        $this->user->businesses()->attach($this->business->id, [
            'role' => 'business-owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->session(['current_business_id' => $this->business->id]);

        app(\App\Managers\TenantManager::class)->clear();
    });

    it('can retrieve AI configuration', function () {
        AIConfiguration::factory()->create([
            'business_id' => $this->business->id,
            'provider' => 'openai',
        ]);

        $response = $this->getJson('/api/v1/ai/configuration');

        $response->assertOk();
        $response->assertJsonPath('data.provider', 'openai');
    });

    it('can update AI configuration', function () {
        $response = $this->putJson('/api/v1/ai/configuration', [
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-20241022',
            'temperature' => 0.3,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.provider', 'anthropic');
    });

    it('returns default configuration when none set', function () {
        $response = $this->getJson('/api/v1/ai/configuration');

        $response->assertOk();
        $response->assertJsonPath('data.provider', 'openai');
    });

    it('can list prompt templates', function () {
        PromptTemplate::factory()->count(3)->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->getJson('/api/v1/prompts');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    });

    it('can create a prompt template', function () {
        $response = $this->postJson('/api/v1/prompts', [
            'name' => 'Support Response',
            'type' => 'conversation',
            'content' => 'You are a support agent for {{business_name}}. Respond to: {{message}}',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Support Response');
        $response->assertJsonPath('data.type', 'conversation');
    });

    it('can test a prompt template', function () {
        $template = PromptTemplate::factory()->create([
            'business_id' => $this->business->id,
            'content' => 'Hello {{name}}, welcome to {{company}}!',
        ]);

        $response = $this->postJson("/api/v1/prompts/{$template->id}/test", [
            'variables' => ['name' => 'Alice', 'company' => 'Acme'],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.rendered', 'Hello Alice, welcome to Acme!');
    });

    it('can retrieve AI usage', function () {
        AIUsage::factory()->count(2)->create([
            'business_id' => $this->business->id,
        ]);

        $response = $this->getJson('/api/v1/ai/usage');

        $response->assertOk();
        $response->assertJsonStructure(['data', 'totals', 'by_provider']);
    });

    it('can retrieve AI usage totals', function () {
        AIUsage::factory()->create([
            'business_id' => $this->business->id,
            'total_requests' => 100,
            'estimated_cost' => 5.50,
        ]);

        $response = $this->getJson('/api/v1/ai/usage/totals');

        $response->assertOk();
        expect($response->json('data.total_requests'))->toBe(100);
    });

    it('creates AI request log on generate', function () {
        $response = $this->postJson('/api/v1/ai/generate', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ]);

        $response->assertOk();
        expect($response->json('status'))->toBeIn(['completed', 'failed']);
    });

    it('returns provider health/available info', function () {
        $response = $this->getJson('/api/v1/ai/providers/available');

        $response->assertOk();
        expect($response->json('data'))->toHaveKeys(['openai', 'anthropic', 'google', 'openrouter']);
    });

    it('enforces tenant isolation for configurations', function () {
        $otherBusiness = Business::factory()->create();
        AIConfiguration::factory()->create([
            'business_id' => $otherBusiness->id,
            'provider' => 'anthropic',
        ]);

        $response = $this->getJson('/api/v1/ai/configuration');
        $response->assertOk();
        expect($response->json('data.provider'))->not->toBe('anthropic');
    });

    it('allows super admin to manage AI providers', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleRegistry::SUPER_ADMIN);

        $this->actingAs($superAdmin);

        $response = $this->getJson('/api/v1/ai/providers');

        $response->assertOk();
    });

    it('denies provider management to users without the permission', function () {
        $response = $this->getJson('/api/v1/ai/providers');

        $response->assertForbidden();
    });

    it('still exposes provider availability to tenant users', function () {
        $response = $this->getJson('/api/v1/ai/providers/available');

        $response->assertOk();
        expect($response->json('data'))->toHaveKeys(['openai', 'anthropic', 'google', 'openrouter']);
    });
});

// ─── AI Permission Gating Tests ─────────────────────────────────────────

describe('AI permission gating', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->business = Business::factory()->create(['owner_id' => $this->user->id]);
        $this->user->businesses()->attach($this->business->id, [
            'role' => 'business-owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->session(['current_business_id' => $this->business->id]);

        app(\App\Managers\TenantManager::class)->clear();
    });

    it('denies configuration read to viewers', function () {
        $viewer = User::factory()->create();
        $viewer->businesses()->attach($this->business->id, [
            'role' => RoleRegistry::VIEWER,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->actingAs($viewer);

        $this->getJson('/api/v1/ai/configuration')->assertForbidden();
    });

    it('denies configuration update to viewers', function () {
        $viewer = User::factory()->create();
        $viewer->businesses()->attach($this->business->id, [
            'role' => RoleRegistry::VIEWER,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->actingAs($viewer);

        $this->putJson('/api/v1/ai/configuration', ['provider' => 'anthropic'])->assertForbidden();
    });

    it('denies configuration update to managers', function () {
        $manager = User::factory()->create();
        $manager->businesses()->attach($this->business->id, [
            'role' => RoleRegistry::MANAGER,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->actingAs($manager);

        $this->putJson('/api/v1/ai/configuration', ['provider' => 'anthropic'])->assertForbidden();
    });

    it('allows configuration read for owners', function () {
        $this->getJson('/api/v1/ai/configuration')->assertOk();
    });

    it('denies generate to viewers', function () {
        $viewer = User::factory()->create();
        $viewer->businesses()->attach($this->business->id, [
            'role' => RoleRegistry::VIEWER,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->actingAs($viewer);

        $this->postJson('/api/v1/ai/generate', [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ])->assertForbidden();
    });

    it('allows generate for agents', function () {
        $agent = User::factory()->create();
        $agent->businesses()->attach($this->business->id, [
            'role' => RoleRegistry::AGENT,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->actingAs($agent);

        $response = $this->postJson('/api/v1/ai/generate', [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);

        $response->assertOk();
    });

    it('denies usage read to viewers', function () {
        $viewer = User::factory()->create();
        $viewer->businesses()->attach($this->business->id, [
            'role' => RoleRegistry::VIEWER,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->actingAs($viewer);

        $this->getJson('/api/v1/ai/usage')->assertForbidden();
    });

    it('allows usage read for managers', function () {
        $manager = User::factory()->create();
        $manager->businesses()->attach($this->business->id, [
            'role' => RoleRegistry::MANAGER,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->actingAs($manager);

        $response = $this->getJson('/api/v1/ai/usage');

        $response->assertOk();
    });

    it('denies prompt management to agents', function () {
        $agent = User::factory()->create();
        $agent->businesses()->attach($this->business->id, [
            'role' => RoleRegistry::AGENT,
            'status' => 'active',
            'joined_at' => now(),
        ]);
        $this->actingAs($agent);

        $this->getJson('/api/v1/prompts')->assertForbidden();
    });

    it('updates full configuration surface including is_active', function () {
        $response = $this->putJson('/api/v1/ai/configuration', [
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-20241022',
            'temperature' => 0.4,
            'top_p' => 0.9,
            'max_tokens' => 4096,
            'timeout_seconds' => 60,
            'streaming' => true,
            'language' => 'pt',
            'safety_settings' => ['hate_speech' => 'block'],
            'system_prompt' => ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            'is_active' => false,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.provider', 'anthropic');
        $response->assertJsonPath('data.language', 'pt');
        $response->assertJsonPath('data.streaming', true);
        $response->assertJsonPath('data.is_active', false);
        $response->assertJsonPath('data.safety_settings.hate_speech', 'block');
        $response->assertJsonPath('data.system_prompt.content', 'You are a helpful assistant.');
    });
});
