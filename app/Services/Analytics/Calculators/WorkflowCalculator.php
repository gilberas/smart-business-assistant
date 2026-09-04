<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\Models\Business;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowLog;
use Illuminate\Support\Facades\DB;

class WorkflowCalculator extends BaseCalculator
{
    public function summary(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->remember($business, 'workflow_summary', $filter, function () use ($business, $filter) {
            $runQuery = WorkflowRun::where('business_id', $business->id)
                ->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()]);

            $totalRuns = (clone $runQuery)->count();
            $successful = (clone $runQuery)->where('status', 'completed')->count();
            $failed = (clone $runQuery)->where('status', 'failed')->count();
            $pending = (clone $runQuery)->where('status', 'pending')->count();
            $running = (clone $runQuery)->where('status', 'running')->count();

            $successRate = $totalRuns > 0 ? round(($successful / $totalRuns) * 100, 1) : 0;
            $failureRate = $totalRuns > 0 ? round(($failed / $totalRuns) * 100, 1) : 0;

            $avgExecutionTime = (clone $runQuery)
                ->whereNotNull('started_at')
                ->whereNotNull('finished_at')
                ->select(DB::raw('AVG(' . $this->dateDiffSql('started_at', 'finished_at', 'SECOND') . ') as avg_time'))
                ->value('avg_time');

            $mostUsed = Workflow::where('business_id', $business->id)
                ->withCount(['runs' => function ($q) use ($filter) {
                    $q->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()]);
                }])
                ->having('runs_count', '>', 0)
                ->groupBy('workflows.id')
                ->orderBy('runs_count', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'status']);

            $mostSuccessful = WorkflowRun::where('business_id', $business->id)
                ->where('status', 'completed')
                ->select('workflow_id', DB::raw('COUNT(*) as success_count'))
                ->groupBy('workflow_id')
                ->orderBy('success_count', 'desc')
                ->limit(5)
                ->pluck('success_count', 'workflow_id');

            $errorLogs = WorkflowLog::whereIn('workflow_run_id', (clone $runQuery)->pluck('id'))
                ->where('level', 'error')
                ->select('stage', DB::raw('COUNT(*) as count'))
                ->groupBy('stage')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'stage');

            return [
                'total_runs' => $totalRuns,
                'successful' => $successful,
                'failed' => $failed,
                'pending' => $pending,
                'running' => $running,
                'success_rate' => $successRate,
                'failure_rate' => $failureRate,
                'avg_execution_seconds' => $avgExecutionTime ? round((float) $avgExecutionTime, 1) : null,
                'most_used_workflows' => $mostUsed->toArray(),
                'most_successful_workflows' => $mostSuccessful->toArray(),
                'error_stages' => $errorLogs->toArray(),
            ];
        });
    }
}
