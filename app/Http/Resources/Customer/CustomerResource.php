<?php

namespace App\Http\Resources\Customer;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'phone' => $this->phone,
            'phone_alt' => $this->phone_alt,
            'email' => $this->email,
            'status' => $this->status,
            'source' => $this->source,
            'preferred_language' => $this->preferred_language,
            'timezone' => $this->timezone,
            'customer_type' => $this->customer_type,
            'lifecycle_stage' => $this->lifecycle_stage,
            'company_name' => $this->company_name,
            'job_title' => $this->job_title,
            'website_url' => $this->website_url,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'gender' => $this->gender,
            'birthday' => $this->birthday?->toISOString(),
            'nationality' => $this->nationality,
            'social_instagram' => $this->social_instagram,
            'social_facebook' => $this->social_facebook,
            'social_twitter' => $this->social_twitter,
            'social_linkedin' => $this->social_linkedin,
            'tags' => $this->whenLoaded('tags', fn() => CustomerTagResource::collection($this->tags)),
            'tag_names' => $this->whenLoaded('tags', fn() => $this->tags->pluck('name')->toArray(), []),
            'addresses' => $this->whenLoaded('addresses', fn() => CustomerAddressResource::collection($this->addresses)),
            'primary_address' => $this->whenLoaded('primaryAddress', fn() => new CustomerAddressResource($this->primaryAddress)),
            'notes_count' => $this->whenCounted('notes'),
            'total_orders' => $this->total_orders,
            'total_spent' => $this->total_spent,
            'average_order_value' => $this->average_order_value,
            'loyalty_points' => $this->loyalty_points,
            'loyalty_tier' => $this->loyalty_tier,
            'assigned_to' => $this->assigned_to,
            'metadata' => $this->metadata,
            'last_interacted_at' => $this->last_interacted_at?->toISOString(),
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
