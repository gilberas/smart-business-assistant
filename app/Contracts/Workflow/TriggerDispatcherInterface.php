<?php

namespace App\Contracts\Workflow;

use App\Models\Business;

interface TriggerDispatcherInterface
{
    public function dispatch(string $triggerType, Business $business, array $payload = []): void;

    public function dispatchAsync(string $triggerType, Business $business, array $payload = []): void;
}
