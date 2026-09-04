<?php

namespace App\Services\Workflow;

use App\Enums\Workflow\WorkflowRunStatus;
use App\Events\Workflow\WorkflowActionExecuted;
use App\Events\Workflow\WorkflowRunCompleted;
use App\Events\Workflow\WorkflowRunFailed;
use App\Events\Workflow\WorkflowRunStarted;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowAction;
use App\Models\WorkflowCondition;
use App\Models\WorkflowLog;
use App\Models\WorkflowRun;
use App\Models\WorkflowTrigger;
use App\Models\WorkflowVersion;
use App\Repositories\Workflow\WorkflowRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowExecutionService
{
    public function __construct(
        private readonly WorkflowRepository $workflowRepository,
        private readonly ConditionEngineService $conditionEngine,
        private readonly ActionEngineService $actionEngine,
    ) {}

    public function executeAction(WorkflowRun $run, WorkflowAction $action, array $context): array
    {
        $result = $this->actionEngine->execute($run, $action, $context);

        WorkflowActionExecuted::dispatch($run, $action, $result);

        return $result;
    }

    public function retry(WorkflowRun $run): WorkflowRun
    {
        $workflow = $run->workflow;
        if (!$workflow || $run->status->value !== 'failed') {
            throw new \RuntimeException('Workflow run cannot be retried.');
        }

        $run->update([
            'status' => 'retrying',
            'attempts' => $run->attempts + 1,
        ]);

        $triggeredByUser = $run->triggered_by ? User::find($run->triggered_by) : null;

        return $this->execute($workflow, $run->input_payload ?? [], $triggeredByUser, $run->trigger_type);
    }

    public function execute(Workflow $workflow, array $payload = [], ?User $triggeredBy = null, ?string $triggerType = null): WorkflowRun
    {
        $version = $workflow->versions()->with(['triggers', 'conditions', 'actions'])->orderByDesc('version')->first();
        if (!$version) {
            throw new \RuntimeException('Workflow has no version to execute.');
        }

        return DB::transaction(function () use ($workflow, $version, $payload, $triggeredBy, $triggerType) {
            $run = WorkflowRun::create([
                'workflow_id' => $workflow->id,
                'workflow_version_id' => $version->id,
                'business_id' => $workflow->business_id,
                'triggered_by' => $triggeredBy?->id,
                'trigger_type' => $triggerType ?? 'manual',
                'status' => WorkflowRunStatus::Running,
                'execution_mode' => $workflow->execution_mode,
                'attempts' => 1,
                'max_attempts' => config('workflow.max_attempts', 3),
                'input_payload' => $payload,
                'context' => $this->buildContext($workflow, $version, $payload, $triggeredBy, $triggerType),
                'started_at' => now(),
            ]);

            WorkflowRunStarted::dispatch($run);
            $this->log($run, null, 'info', 'trigger', 'Workflow run started', ['workflow_id' => $workflow->id]);

            $branchKey = $this->selectBranch($version, $run->context ?? []);
            $actions = $this->actionsForBranch($version, $branchKey);

            try {
                foreach ($actions as $action) {
                    $result = $this->executeAction($run, $action, $run->context ?? []);
                    if (($run->fresh()->status?->value ?? $run->fresh()->status) === WorkflowRunStatus::Waiting->value) {
                        break;
                    }
                }

                $run->update([
                    'status' => WorkflowRunStatus::Completed,
                    'finished_at' => now(),
                    'result' => ['branch_key' => $branchKey, 'actions' => $actions->pluck('type')->values()->all()],
                ]);

                WorkflowRunCompleted::dispatch($run->fresh());
                $this->log($run->fresh(), null, 'info', 'completion', 'Workflow run completed', ['branch_key' => $branchKey]);
            } catch (\Throwable $e) {
                $run->update([
                    'status' => WorkflowRunStatus::Failed,
                    'finished_at' => now(),
                    'result' => ['error' => $e->getMessage()],
                ]);

                $willRetry = $run->attempts < $run->max_attempts;
                WorkflowRunFailed::dispatch($run->fresh(), $e, $willRetry);
                $this->log($run->fresh(), null, 'error', 'failure', 'Workflow run failed', ['error' => $e->getMessage()]);

                throw $e;
            }

            return $run->fresh(['logs', 'workflow', 'version']);
        });
    }

    public function log(WorkflowRun $run, ?WorkflowAction $action, string $level, string $stage, string $message, array $payload = []): WorkflowLog
    {
        return WorkflowLog::create([
            'workflow_run_id' => $run->id,
            'workflow_action_id' => $action?->id,
            'level' => $level,
            'stage' => $stage,
            'message' => $message,
            'payload' => $payload,
        ]);
    }

    private function buildContext(Workflow $workflow, WorkflowVersion $version, array $payload, ?User $triggeredBy, ?string $triggerType): array
    {
        return [
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'business_id' => $workflow->business_id,
            'trigger_type' => $triggerType ?? 'manual',
            'triggered_by' => $triggeredBy?->id,
            'payload' => $payload,
            'variables' => $workflow->variables ?? [],
            'metadata' => $workflow->metadata ?? [],
            ...$payload,
        ];
    }

    private function selectBranch(WorkflowVersion $version, array $context): ?string
    {
        $conditions = $version->conditions->sortBy('priority');
        if ($conditions->isEmpty()) {
            return null;
        }

        $grouped = $conditions->groupBy(fn (WorkflowCondition $condition) => $condition->group_key ?? 'default');
        foreach ($grouped as $groupKey => $groupConditions) {
            $passes = $groupConditions->every(fn (WorkflowCondition $condition) => $this->conditionEngine->matches($condition, $context));
            if ($passes) {
                return $groupKey === 'default' ? null : (string) $groupKey;
            }
        }

        return null;
    }

    private function actionsForBranch(WorkflowVersion $version, ?string $branchKey): Collection
    {
        return $version->actions
            ->where('is_enabled', true)
            ->filter(fn (WorkflowAction $action) => $branchKey === null || $action->branch_key === null || $action->branch_key === $branchKey)
            ->sortBy('position')
            ->values();
    }
}