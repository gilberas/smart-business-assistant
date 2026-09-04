<?php

namespace App\Events\AI;

use App\Models\AI\AIRequest;
use Illuminate\Foundation\Events\Dispatchable;

class AIRequestStarted
{
    use Dispatchable;

    public function __construct(
        public readonly AIRequest $request,
    ) {}
}
