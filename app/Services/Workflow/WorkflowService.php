<?php

namespace App\Services\Workflow;

use App\Contracts\Workflow\WorkflowServiceInterface;
use App\DTOs\Workflow\WorkflowDefinitionDTO;
use App\Models\Business;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Repositories\Workflow\WorkflowRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkflowService implements WorkflowServiceInterface
{
    public function __construct(
        private readonly WorkflowRepository $repository,
    ) {}

    public function paginate(Business $business, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginateForBusiness($business->id, $filters, $perPage);
    }

    public function find(Business $business, int $id): ?Workflow
    {
        return $this->repository->findForBusiness($business->id, $id);
    }

    public function create(Business $business, User $user, array $data): Workflow
    {
        return $this->repository->create($business, $user, WorkflowDefinitionDTO::fromArray($data));
    }

    public function update(Workflow $workflow, array $data): Workflow
    {
        $this->repository->update($workflow, $data);

        return $workflow->fresh(['owner', 'versions', 'variablesDefinition']);
    }

    public function delete(Workflow $workflow): bool
    {
        return $this->repository->delete($workflow);
    }

    public function enable(Workflow $workflow): Workflow
    {
        $workflow->update(['status' => 'active', 'enabled_at' => now()]);

        return $workflow->fresh();
    }

    public function disable(Workflow $workflow): Workflow
    {
        $workflow->update(['status' => 'paused']);

        return $workflow->fresh();
    }

    public function createVersion(Workflow $workflow, User $user, array $data): WorkflowVersion
    {
        return $this->repository->createVersion($workflow, $user, WorkflowDefinitionDTO::fromArray($data));
    }

    public function versions(Workflow $workflow): iterable
    {
        return $this->repository->listVersions($workflow);
    }

    public function runs(Workflow $workflow, int $perPage): LengthAwarePaginator
    {
        return $this->repository->listRuns($workflow, $perPage);
    }

    public function logs(WorkflowRun $run, int $perPage): LengthAwarePaginator
    {
        return $this->repository->listLogs($run, $perPage);
    }

    public function export(Workflow $workflow): array
    {
        $workflow->load(['versions.triggers', 'versions.conditions', 'versions.actions', 'variablesDefinition']);

        return [
            'name' => $workflow->name,
            'description' => $workflow->description,
            'category' => $workflow->category,
            'execution_mode' => $workflow->execution_mode?->value,
            'variables' => $workflow->variables,
            'metadata' => $workflow->metadata,
            'versions' => $workflow->versions->map(fn (WorkflowVersion $v) => [
                'version' => $v->version,
                'definition' => $v->definition,
                'triggers' => $v->triggers->toArray(),
                'conditions' => $v->conditions->toArray(),
                'actions' => $v->actions->toArray(),
            ]),
        ];
    }

    public function import(Business $business, User $user, array $data): Workflow
    {
        return $this->repository->create($business, $user, WorkflowDefinitionDTO::fromArray($data));
    }
}
