<?php

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\DTOs\Analytics\DateRangeDTO;
use App\Models\AiRequestLog;
use App\Models\BroadcastCampaign;
use App\Models\BroadcastRecipient;
use App\Models\Business;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\HandoverLog;
use App\Models\KnowledgeArticle;
use App\Models\Message;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function defaultFilter(): AnalyticsFilterDTO
{
    return new AnalyticsFilterDTO(DateRangeDTO::fromPreset('all_time'));
}

function findKpi(array $kpis, string $key): mixed
{
    foreach ($kpis as $kpi) {
        if ($kpi['key'] === $key) {
            return $kpi['value'];
        }
    }
    return null;
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user2 = User::factory()->create();
    $this->business = Business::factory()->create(['owner_id' => $this->user->id]);
    $this->business2 = Business::factory()->create(['owner_id' => $this->user2->id]);
    $this->user->businesses()->attach($this->business, ['role' => 'business-admin']);
    $this->user2->businesses()->attach($this->business2, ['role' => 'business-admin']);

    $this->customer = Customer::factory()->create(['business_id' => $this->business->id]);
    $this->customer2 = Customer::factory()->create(['business_id' => $this->business->id]);

    $this->analytics = app(AnalyticsService::class);
});

it('returns overview KPIs', function () {
    Conversation::factory()->count(5)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    Conversation::factory()->count(3)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer2->id,
        'status' => 'closed',
    ]);

    $data = $this->analytics->overview($this->business, defaultFilter());

    expect(findKpi($data['kpis'], 'total_conversations'))->toBe(8);
    expect(findKpi($data['kpis'], 'resolved_conversations'))->toBe(3);
    expect(findKpi($data['kpis'], 'total_customers'))->toBe(2);
    expect($data)->toHaveKey('conversation_trend');
    expect($data)->toHaveKey('customer_trend');
});

it('returns conversations analytics with time series', function () {
    $conv = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'created_at' => now()->subDays(2),
    ]);
    Message::factory()->count(4)->create([
        'conversation_id' => $conv->id,
        'business_id' => $this->business->id,
    ]);

    $data = $this->analytics->conversations($this->business, defaultFilter());

    expect($data['total'])->toBe(1);
    expect($data)->toHaveKey('open');
    expect($data)->toHaveKey('closed');
    expect($data)->toHaveKey('trend');
    expect($data)->toHaveKey('breakdown');
    expect($data)->toHaveKey('resolution_rate');
});

it('returns customers analytics', function () {
    Customer::factory()->count(3)->create(['business_id' => $this->business->id]);

    $data = $this->analytics->customers($this->business, defaultFilter());

    expect($data['total'])->toBe(5);
    expect($data)->toHaveKey('new');
    expect($data)->toHaveKey('interacted');
    expect($data)->toHaveKey('returning');
    expect($data)->toHaveKey('trend');
});

it('returns AI analytics', function () {
    $conv = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    AiRequestLog::create([
        'business_id' => $this->business->id,
        'conversation_id' => $conv->id,
        'provider' => 'openai',
        'model' => 'gpt-4',
        'prompt_tokens' => 100,
        'completion_tokens' => 50,
        'total_tokens' => 150,
        'duration_ms' => 500,
        'cost' => 0.003,
        'response_status' => 'success',
    ]);
    AiRequestLog::create([
        'business_id' => $this->business->id,
        'conversation_id' => $conv->id,
        'provider' => 'openai',
        'model' => 'gpt-4',
        'prompt_tokens' => 200,
        'completion_tokens' => 100,
        'total_tokens' => 300,
        'duration_ms' => 800,
        'cost' => 0.006,
        'response_status' => 'success',
    ]);

    $data = $this->analytics->ai($this->business, defaultFilter());

    expect($data['total_requests'])->toBe(2);
    expect($data['successful_requests'])->toBe(2);
    expect($data['failed_requests'])->toBe(0);
    expect($data)->toHaveKey('avg_latency_ms');
    expect($data)->toHaveKey('estimated_cost');
    expect($data)->toHaveKey('by_provider');
    expect($data)->toHaveKey('by_model');
});

it('returns agent analytics', function () {
    $conv = Conversation::factory()->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'assigned_to' => $this->user->id,
        'status' => 'closed',
    ]);
    Message::factory()->count(3)->create([
        'conversation_id' => $conv->id,
        'business_id' => $this->business->id,
        'sender_type' => 'agent',
        'sender_id' => $this->user->id,
    ]);
    HandoverLog::create([
        'conversation_id' => $conv->id,
        'business_id' => $this->business->id,
        'initiated_by' => $this->user->id,
        'assigned_to' => $this->user->id,
        'reason' => 'test',
        'handover_type' => 'manual',
    ]);

    $data = $this->analytics->agents($this->business, defaultFilter());

    expect($data['total_assigned'])->toBe(1);
    expect($data['total_resolved'])->toBe(1);
    expect($data['total_agent_messages'])->toBe(3);
    expect($data)->toHaveKey('agent_stats');
});

it('returns campaign analytics', function () {
    $campaign = BroadcastCampaign::create([
        'business_id' => $this->business->id,
        'name' => 'Test Campaign',
        'status' => \App\Enums\Broadcast\BroadcastCampaignStatus::Completed,
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);
    BroadcastRecipient::create([
        'campaign_id' => $campaign->id,
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
        'status' => 'delivered',
    ]);
    BroadcastRecipient::create([
        'campaign_id' => $campaign->id,
        'business_id' => $this->business->id,
        'customer_id' => $this->customer2->id,
        'status' => 'delivered',
    ]);

    $data = $this->analytics->campaigns($this->business, defaultFilter());

    expect($data['total_campaigns'])->toBe(1);
    expect($data['total_recipients'])->toBe(2);
});

it('returns workflow analytics', function () {
    $workflow = Workflow::create([
        'business_id' => $this->business->id,
        'owner_id' => $this->user->id,
        'name' => 'Test Workflow',
        'status' => \App\Enums\Workflow\WorkflowStatus::Active,
        'category' => 'customer_service',
    ]);
    WorkflowRun::create([
        'workflow_id' => $workflow->id,
        'business_id' => $this->business->id,
        'trigger_type' => 'manual',
        'status' => \App\Enums\Workflow\WorkflowRunStatus::Completed,
        'started_at' => now()->subMinutes(10),
        'finished_at' => now(),
    ]);

    $data = $this->analytics->workflows($this->business, defaultFilter());

    expect($data['total_runs'])->toBe(1);
    expect($data['successful'])->toBe(1);
});

it('returns knowledge analytics', function () {
    KnowledgeArticle::factory()->count(4)->create([
        'business_id' => $this->business->id,
    ]);

    $data = $this->analytics->knowledge($this->business, defaultFilter());

    expect($data['total_articles'])->toBe(4);
    expect($data)->toHaveKey('published_articles');
    expect($data)->toHaveKey('total_views');
    expect($data)->toHaveKey('helpful_rate');
});

it('enforces tenant isolation', function () {
    Conversation::factory()->count(5)->create([
        'business_id' => $this->business->id,
        'customer_id' => $this->customer->id,
    ]);
    $otherCustomer = Customer::factory()->create(['business_id' => $this->business2->id]);
    Conversation::factory()->count(10)->create([
        'business_id' => $this->business2->id,
        'customer_id' => $otherCustomer->id,
    ]);

    $data = $this->analytics->overview($this->business, defaultFilter());

    expect(findKpi($data['kpis'], 'total_conversations'))->toBe(5);
});

it('handles empty data gracefully', function () {
    $data = $this->analytics->overview($this->business, defaultFilter());
    expect(findKpi($data['kpis'], 'total_conversations'))->toBe(0);

    $data = $this->analytics->conversations($this->business, defaultFilter());
    expect($data['total'])->toBe(0);

    $data = $this->analytics->ai($this->business, defaultFilter());
    expect($data['total_requests'])->toBe(0);

    $data = $this->analytics->knowledge($this->business, defaultFilter());
    expect($data['total_articles'])->toBe(0);
});
