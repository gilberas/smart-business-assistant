<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsApp\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageStatusUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly string $newStatus,
        public readonly array $metadata = [],
    ) {}
}
