<?php

namespace App\Listeners\AI;

use App\Events\AI\AIRequestCompleted;
use App\Events\AI\AIRequestFailed;
use Illuminate\Support\Facades\Log;

class LogAIRequest
{
    public function handle(AIRequestCompleted|AIRequestFailed $event): void
    {
        $request = $event->request;

        $context = [
            'request_id' => $request->id,
            'business_id' => $request->business_id,
            'provider' => $request->provider,
            'model' => $request->model,
            'duration_ms' => $request->duration_ms,
            'total_tokens' => $request->total_tokens,
            'estimated_cost' => $request->estimated_cost,
        ];

        if ($event instanceof AIRequestFailed) {
            $context['error'] = $event->exception->getMessage();
            Log::warning('AI Request Failed', $context);
        } else {
            Log::info('AI Request Completed', $context);
        }
    }
}
