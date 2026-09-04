<?php

namespace App\Events\Broadcast;

use App\Models\BroadcastCampaign;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;

class CampaignResumed
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly BroadcastCampaign $campaign,
    ) {}
}
