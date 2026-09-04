<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsApp\WhatsAppAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $payload,
        public readonly array $headers,
        public readonly ?WhatsAppAccount $account = null,
    ) {}
}
