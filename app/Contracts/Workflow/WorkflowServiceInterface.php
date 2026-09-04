<?php

namespace App\Contracts\Workflow;

use App\Models\Business;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WorkflowServiceInterface
{
    public function paginate(Business $business, array $filters, int $perPage): LengthAwarePaginator;

    public function find(Business $business, int $id): ?Workflow;

    public function create(Business $business, User $user, array $data): Workflow;

    public function update(Workflow $workflow, array $data): Workflow;

    public function delete(Workflow $workflow): bool;

    public function enable(Workflow $workflow): Workflow;

    public function disable(Workflow $workflow): Workflow;

    public function createVersion(Workflow $workflow, User $user, array $data): WorkflowVersion;

    public function versions(Workflow $workflow): iterable;

    public function runs(Workflow $workflow, int $perPage): LengthAwarePaginator;

    public function logs(WorkflowRun $run, int $perPage): LengthAwarePaginator;

    public function export(Workflow $workflow): array;

    public function import(Business $business, User $user, array $data): Workflow;
}
