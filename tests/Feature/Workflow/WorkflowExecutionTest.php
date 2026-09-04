<?php

use App\Enums\Workflow\WorkflowActionType;
use App\Enums\Workflow\WorkflowRunStatus;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowRun;
use App\Models\Business;
use App\Models\User;
use App\Services\Workflow\WorkflowExecutionService;
use App\Services\Workflow\WorkflowConditionEngineService;
use App\Services\Workflow\WorkflowActionEngineService;
use App\Repositories\Workflow\WorkflowRepository;
use App\DTOs\Workflow\WorkflowDefinitionDTO;

beforeEach(function () {
    $this->business = Business::factory()->create();
    $this->user = User::factory()->create();
    $this->business->members()->attach($this->user, ['role' => 'owner']);

    $this->executionService = app(WorkflowExecutionService::class);
});

it('executes a workflow successfully', function () {
    $workflow = createWorkflowWithActions();

    $run = $this->executionService->execute($workflow, ['conversation_id' => 1], $this->user, 'manual');

    expect($run->status)->toBe(WorkflowRunStatus::Completed);
    expect($run->trigger_type)->toBe('manual');
    expect($run->attempts)->toBe(1);
    expect($run->logs)->not->toBeEmpty();
});

it('creates workflow run records on execution', function () {
    $workflow = createWorkflowWithActions();

    $run = $this->executionService->execute($workflow, [], $this->user, 'manual');

    expect(WorkflowRun::count())->toBe(1);
    expect($run->workflow_id)->toBe($workflow->id);
});

it('selects correct branch based on conditions', function () {
    $workflow = Workflow::factory()->create([
        'business_id' => $this->business->id,
        'owner_id' => $this->user->id,
        'status' => 'active',
    ]);

    $version = $workflow->versions()->create([
        'version' => 1,
        'definition' => [],
        'created_by' => $this->user->id,
    ]);

    // Branch A: if conversation_id > 100
    $version->conditions()->create([
        'group_key' => 'branch_a',
        'field' => 'conversation_id',
        'operator' => 'greater_than',
        'value' => 100,
        'priority' => 0,
    ]);

    $version->actions()->create([
        'branch_key' => 'branch_a',
        'type' => 'add_internal_note',
        'name' => 'Branch A action',
        'payload' => ['content' => 'Branch A executed'],
        'position' => 0,
        'is_enabled' => true,
    ]);

    // Branch B: if conversation_id <= 100
    $version->conditions()->create([
        'group_key' => 'branch_b',
        'field' => 'conversation_id',
        'operator' => 'less_than_or_equal',
        'value' => 100,
        'priority' => 1,
    ]);

    $version->actions()->create([
        'branch_key' => 'branch_b',
        'type' => 'add_internal_note',
        'name' => 'Branch B action',
        'payload' => ['content' => 'Branch B executed'],
        'position' => 0,
        'is_enabled' => true,
    ]);

    // Execute with conversation_id = 200 (should trigger branch A)
    $run = $this->executionService->execute($workflow, ['conversation_id' => 200], $this->user, 'manual');
    expect($run->result['branch_key'])->toBe('branch_a');

    // Execute with conversation_id = 50 (should trigger branch B)
    $run2 = $this->executionService->execute($workflow, ['conversation_id' => 50], $this->user, 'manual');
    expect($run2->result['branch_key'])->toBe('branch_b');
});

it('throws exception when workflow has no version', function () {
    $workflow = Workflow::factory()->create([
        'business_id' => $this->business->id,
        'owner_id' => $this->user->id,
    ]);

    $this->executionService->execute($workflow, [], $this->user, 'manual');
})->throws(\RuntimeException::class, 'Workflow has no version to execute.');

function createWorkflowWithActions(): Workflow
{
    $workflow = Workflow::factory()->create([
        'business_id' => test()->business->id,
        'owner_id' => test()->user->id,
        'status' => 'active',
    ]);

    $version = $workflow->versions()->create([
        'version' => 1,
        'definition' => [],
        'created_by' => test()->user->id,
    ]);

    $version->triggers()->create([
        'type' => 'manual',
        'name' => 'Manual',
        'payload' => [],
        'is_enabled' => true,
    ]);

    $version->actions()->create([
        'type' => 'add_internal_note',
        'name' => 'Note',
        'payload' => ['content' => 'Workflow executed'],
        'position' => 0,
        'is_enabled' => true,
    ]);

    return $workflow->fresh();
}
