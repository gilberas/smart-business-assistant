<?php

use App\Enums\Workflow\WorkflowStatus;
use App\Models\Business;
use App\Models\User;
use App\Models\Workflow;
use App\Services\Workflow\WorkflowService;
use App\DTOs\Workflow\WorkflowDefinitionDTO;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business->members()->attach($this->user, ['role' => 'owner']);
    $this->service = app(WorkflowService::class);
});

it('creates a workflow', function () {
    $workflow = $this->service->create($this->business, $this->user, [
        'name' => 'Test Workflow',
        'description' => 'Test description',
        'category' => 'support',
    ]);

    expect($workflow)->toBeInstanceOf(Workflow::class);
    expect($workflow->name)->toBe('Test Workflow');
    expect($workflow->category)->toBe('support');
    expect($workflow->status)->toBe(WorkflowStatus::Draft);
});

it('creates a version with the workflow', function () {
    $workflow = $this->service->create($this->business, $this->user, [
        'name' => 'Versioned Workflow',
        'category' => 'notifications',
        'triggers' => [
            ['type' => 'manual', 'name' => 'Manual', 'payload' => []],
        ],
        'actions' => [
            ['type' => 'add_internal_note', 'name' => 'Note', 'payload' => ['content' => 'test'], 'position' => 0],
        ],
    ]);

    expect($workflow->versions)->toHaveCount(1);
    expect($workflow->version)->toBe(2);
});

it('paginates workflows for a business', function () {
    Workflow::factory()->count(5)->create(['business_id' => $this->business->id, 'owner_id' => $this->user->id]);

    $result = $this->service->paginate($this->business, [], 15);

    expect($result->total())->toBe(5);
});

it('filters workflows by status', function () {
    Workflow::factory()->count(2)->create(['business_id' => $this->business->id, 'owner_id' => $this->user->id, 'status' => 'draft']);
    Workflow::factory()->count(3)->active()->create(['business_id' => $this->business->id, 'owner_id' => $this->user->id]);

    $result = $this->service->paginate($this->business, ['status' => 'active'], 15);

    expect($result->total())->toBe(3);
});

it('enables and disables a workflow', function () {
    $workflow = Workflow::factory()->create(['business_id' => $this->business->id, 'owner_id' => $this->user->id]);

    $enabled = $this->service->enable($workflow);
    expect($enabled->status->value)->toBe('active');
    expect($enabled->enabled_at)->not->toBeNull();

    $disabled = $this->service->disable($workflow->fresh());
    expect($disabled->status->value)->toBe('paused');
});

it('deletes a workflow', function () {
    $workflow = Workflow::factory()->create(['business_id' => $this->business->id, 'owner_id' => $this->user->id]);

    $this->service->delete($workflow);

    expect(Workflow::find($workflow->id))->toBeNull();
});
