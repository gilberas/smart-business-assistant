<?php

namespace App\Events\AI;

use App\Models\AI\AIProvider;
use Illuminate\Foundation\Events\Dispatchable;

class ProviderHealthChecked
{
    use Dispatchable;

    public function __construct(
        public readonly AIProvider $provider,
        public readonly bool $healthy,
        public readonly int $latencyMs,
        public readonly ?string $error = null,
    ) {}
}
