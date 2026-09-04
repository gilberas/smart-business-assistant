<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'source' => $this->source,
            'customer_type' => $this->customer_type,
            'lifecycle_stage' => $this->lifecycle_stage,
            'company_name' => $this->company_name,
            'avatar_url' => $this->avatar_url,
            'tags' => $this->whenLoaded('tags', fn() => CustomerTagResource::collection($this->tags)),
            'city' => $this->whenLoaded('primaryAddress', fn() => $this->primaryAddress?->city),
            'country' => $this->whenLoaded('primaryAddress', fn() => $this->primaryAddress?->country),
            'total_orders' => $this->total_orders,
            'total_spent' => $this->total_spent,
            'last_interacted_at' => $this->last_interacted_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
