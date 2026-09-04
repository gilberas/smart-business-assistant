<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsApp\Message;
use App\Models\WhatsApp\WhatsAppAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly WhatsAppAccount $account,
        public readonly array $rawPayload = [],
    ) {}
}
