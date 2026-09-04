<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsApp\WhatsAppAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppAccountConnected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WhatsAppAccount $account,
        public readonly string $wabaId,
        public readonly array $metadata = [],
    ) {}
}
