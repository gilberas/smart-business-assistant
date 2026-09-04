<?php

namespace App\Contracts\Broadcast;

use App\Models\BroadcastCampaign;
use App\Models\BroadcastRecipient;

interface DeliveryServiceInterface
{
    public function send(BroadcastRecipient $recipient): array;

    public function handleDeliveryCallback(BroadcastRecipient $recipient, array $webhookPayload): void;

    public function batchQueue(BroadcastCampaign $campaign): void;

    public function retryFailed(BroadcastCampaign $campaign): int;
}
