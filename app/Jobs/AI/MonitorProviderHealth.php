<?php

namespace App\Jobs\AI;

use App\Services\AI\HealthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class MonitorProviderHealth implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(HealthService $healthService): void
    {
        $healthService->checkAll();
    }
}
