<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message_id' => $this->message_id,
            'status' => $this->status,
            'timestamp' => $this->timestamp?->toISOString(),
            'error_code' => $this->error_code,
            'error_title' => $this->error_title,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
