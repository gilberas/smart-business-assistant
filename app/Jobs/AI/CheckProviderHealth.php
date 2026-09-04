<?php

namespace App\Jobs\AI;

use App\Models\AI\AIRequest;
use App\Services\AI\HealthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class CheckProviderHealth implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public int $providerId,
    ) {}

    public function handle(HealthService $healthService): void
    {
        $healthService->checkProvider($this->providerId);
    }
}
