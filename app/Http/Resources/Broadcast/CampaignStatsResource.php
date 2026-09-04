<?php

namespace App\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total' => $this->total,
            'pending' => $this->pending,
            'queued' => $this->queued,
            'sent' => $this->sent,
            'delivered' => $this->delivered,
            'read' => $this->read,
            'failed' => $this->failed,
            'expired' => $this->expired,
            'rejected' => $this->rejected,
            'delivery_rate' => $this->deliveryRate,
            'read_rate' => $this->readRate,
        ];
    }
}
