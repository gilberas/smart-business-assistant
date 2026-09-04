<?php

namespace App\Jobs\WhatsApp;

use App\Events\WhatsApp\MessageFailed;
use App\Events\WhatsApp\MessageSent;
use App\Models\WhatsApp\Message;
use App\Repositories\WhatsApp\DeliveryLogRepository;
use App\Repositories\WhatsApp\MessageRepository;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOutgoingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = [10, 30, 60];

    public function __construct(
        public readonly Message $message,
    ) {
        $this->onQueue(config('whatsapp.queues.outgoing', 'outgoing'));
    }

    public function handle(
        WhatsAppService $whatsAppService,
        MessageRepository $messageRepo,
        DeliveryLogRepository $deliveryLogRepo,
    ): void {
        $startTime = microtime(true);

        try {
            $response = $whatsAppService->sendMessage($this->message);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $deliveryLogRepo->create([
                'business_id' => $this->message->business_id,
                'message_id' => $this->message->id,
                'event_type' => 'send',
                'status' => 'success',
                'response_code' => $response['status_code'] ?? 200,
                'response_body' => json_encode($response['body'] ?? []),
                'attempted_at' => now(),
                'duration_ms' => $durationMs,
            ]);

            $metaMessageId = $response['body']['messages'][0]['id'] ?? null;

            $messageRepo->update($this->message, [
                'meta_message_id' => $metaMessageId,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            MessageSent::dispatch($this->message->fresh(), $response);

        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $deliveryLogRepo->create([
                'business_id' => $this->message->business_id,
                'message_id' => $this->message->id,
                'event_type' => 'send',
                'status' => 'failed',
                'response_code' => 0,
                'response_body' => $e->getMessage(),
                'attempted_at' => now(),
                'duration_ms' => $durationMs,
            ]);

            Log::error('Failed to send WhatsApp message', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $messageRepo->markAsFailed($this->message, [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);
                MessageFailed::dispatch($this->message, (string) $e->getCode(), $e->getMessage());
            }

            throw $e;
        }
    }
}
