<?php

namespace App\Jobs\WhatsApp;

use App\Models\WhatsApp\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly Message $message,
    ) {
        $this->onQueue(config('whatsapp.queues.outgoing', 'outgoing'));
    }

    public function handle(): void
    {
        Log::info('Retrying failed message', [
            'message_id' => $this->message->id,
            'uuid' => $this->message->uuid,
        ]);

        SendOutgoingMessage::dispatch($this->message);
    }
}
