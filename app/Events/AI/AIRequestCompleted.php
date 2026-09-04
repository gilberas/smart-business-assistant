<?php

namespace App\Events\AI;

use App\DTOs\AI\AIResponseDTO;
use App\Models\AI\AIRequest;
use Illuminate\Foundation\Events\Dispatchable;

class AIRequestCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly AIRequest $request,
        public readonly AIResponseDTO $response,
    ) {}
}
