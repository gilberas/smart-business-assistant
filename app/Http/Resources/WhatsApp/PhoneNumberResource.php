<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhoneNumberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'whatsapp_account_id' => $this->whatsapp_account_id,
            'phone_number' => $this->phone_number,
            'phone_number_id' => $this->phone_number_id,
            'display_phone' => $this->display_phone ?? $this->display_phone_number,
            'verified_name' => $this->verified_name,
            'quality_rating' => $this->quality_rating,
            'status' => $this->status?->value ?? $this->status,
            'code_verification_status' => $this->code_verification_status,
            'pin_enabled' => $this->pin_enabled,
            'messaging_limit_tier' => $this->messaging_limit_tier,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
