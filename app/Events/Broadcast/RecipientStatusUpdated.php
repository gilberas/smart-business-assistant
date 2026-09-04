<?php

namespace App\Events\Broadcast;

use App\Models\BroadcastRecipient;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class RecipientStatusUpdated
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly BroadcastRecipient $recipient,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
