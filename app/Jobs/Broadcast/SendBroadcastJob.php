<?php

namespace App\Jobs\Broadcast;

use App\Models\BroadcastRecipient;
use App\Services\Broadcast\DeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendBroadcastJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function __construct(
        public readonly BroadcastRecipient $recipient,
    ) {}

    public function handle(DeliveryService $deliveryService): void
    {
        $result = $deliveryService->send($this->recipient);

        if ($result['status'] === 'failed' && config('broadcast.sending.retry_on_failure', true)) {
            $maxRetries = config('broadcast.sending.max_retries', 3);
            $attempt = $this->recipient->fresh()->metadata['retry_count'] ?? 0;

            if ($attempt < $maxRetries) {
                $delay = config('broadcast.sending.retry_delay_minutes', 5);
                SendBroadcastJob::dispatch($this->recipient)
                    ->delay(now()->addMinutes($delay))
                    ->onQueue(config('broadcast.queues.delivery', 'delivery'));
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Broadcast send job failed', [
            'recipient_id' => $this->recipient->id,
            'campaign_id' => $this->recipient->campaign_id,
            'error' => $e->getMessage(),
        ]);
    }
}
