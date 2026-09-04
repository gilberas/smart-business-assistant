<?php

namespace App\Repositories\Workflow;

use App\DTOs\Workflow\WorkflowDefinitionDTO;
use App\Models\Business;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Models\WorkflowLog;
use App\Models\WorkflowRun;
use App\Models\WorkflowTrigger;
use App\Models\WorkflowVariable;
use App\Models\WorkflowVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkflowRepository
{
    public function __construct(private readonly Workflow $model) {}

    public function paginateForBusiness(int $businessId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['owner', 'latestVersion'])
            ->where('business_id', $businessId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($builder) use ($filters) {
                $builder->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    public function findForBusiness(int $businessId, int $workflowId): ?Workflow
    {
        return $this->model->with(['owner', 'versions.triggers', 'versions.conditions', 'versions.actions', 'variablesDefinition', 'runs.logs'])
            ->where('business_id', $businessId)
            ->whereKey($workflowId)
            ->first();
    }

    public function create(Business $business, User $owner, WorkflowDefinitionDTO $definition): Workflow
    {
        $workflow = $this->model->create([
            'business_id' => $business->id,
            'owner_id' => $owner->id,
            'name' => $definition->name,
            'description' => $definition->description,
            'category' => $definition->category,
            'status' => $definition->status,
            'execution_mode' => $definition->executionMode,
            'variables' => $definition->variables,
            'metadata' => $definition->metadata,
            'version' => 1,
        ]);

        $this->createVersion($workflow, $owner, $definition);

        return $workflow->fresh();
    }

    public function update(Workflow $workflow, array $data): bool
    {
        return $workflow->update($data);
    }

    public function delete(Workflow $workflow): bool
    {
        return (bool) $workflow->delete();
    }

    public function createVersion(Workflow $workflow, User $creator, WorkflowDefinitionDTO $definition): WorkflowVersion
    {
        $version = $workflow->versions()->create([
            'version' => $workflow->version,
            'definition' => $definition->metadata + [
                'workflow' => [
                    'name' => $definition->name,
                    'description' => $definition->description,
                    'category' => $definition->category,
                    'status' => $definition->status->value,
                    'execution_mode' => $definition->executionMode->value,
                ],
                'variables' => $definition->variables,
                'triggers' => $definition->triggers,
                'conditions' => $definition->conditions,
                'actions' => $definition->actions,
            ],
            'created_by' => $creator->id,
            'change_summary' => $definition->metadata['change_summary'] ?? null,
        ]);

        foreach ($definition->variables as $variable) {
            $version->workflow->variablesDefinition()->create([
                'workflow_version_id' => $version->id,
                'business_id' => $workflow->business_id,
                'name' => $variable['name'],
                'data_type' => $variable['data_type'] ?? 'string',
                'default_value' => $variable['default_value'] ?? null,
                'description' => $variable['description'] ?? null,
                'is_required' => (bool) ($variable['is_required'] ?? false),
            ]);
        }

        foreach ($definition->triggers as $trigger) {
            $createdTrigger = $version->triggers()->create([
                'type' => $trigger['type'],
                'name' => $trigger['name'] ?? ucfirst($trigger['type']),
                'payload' => $trigger['payload'] ?? [],
                'is_enabled' => (bool) ($trigger['is_enabled'] ?? true),
            ]);

            foreach (($trigger['conditions'] ?? []) as $condition) {
                $version->conditions()->create([
                    'workflow_trigger_id' => $createdTrigger->id,
                    'group_key' => $condition['group_key'] ?? $trigger['group_key'] ?? null,
                    'field' => $condition['field'],
                    'operator' => $condition['operator'],
                    'value' => $condition['value'] ?? null,
                    'boolean_connector' => $condition['boolean_connector'] ?? 'and',
                    'priority' => (int) ($condition['priority'] ?? 0),
                ]);
            }
        }

        foreach ($definition->conditions as $condition) {
            $version->conditions()->create([
                'workflow_trigger_id' => $condition['workflow_trigger_id'] ?? null,
                'group_key' => $condition['group_key'] ?? null,
                'field' => $condition['field'],
                'operator' => $condition['operator'],
                'value' => $condition['value'] ?? null,
                'boolean_connector' => $condition['boolean_connector'] ?? 'and',
                'priority' => (int) ($condition['priority'] ?? 0),
            ]);
        }

        foreach ($definition->actions as $action) {
            $version->actions()->create([
                'workflow_condition_id' => $action['workflow_condition_id'] ?? null,
                'branch_key' => $action['branch_key'] ?? null,
                'type' => $action['type'],
                'name' => $action['name'] ?? ucfirst($action['type']),
                'payload' => $action['payload'] ?? [],
                'position' => (int) ($action['position'] ?? 0),
                'is_enabled' => (bool) ($action['is_enabled'] ?? true),
            ]);
        }

        $workflow->update(['version' => $workflow->version + 1]);

        return $version;
    }

    public function listVersions(Workflow $workflow)
    {
        return $workflow->versions()->with(['creator', 'triggers', 'conditions', 'actions'])->orderByDesc('version')->get();
    }

    public function listRuns(Workflow $workflow, int $perPage = 20): LengthAwarePaginator
    {
        return $workflow->runs()->with('logs')->orderByDesc('created_at')->paginate($perPage);
    }

    public function listLogs(WorkflowRun $run, int $perPage = 50): LengthAwarePaginator
    {
        return $run->logs()->orderByDesc('created_at')->paginate($perPage);
    }
}