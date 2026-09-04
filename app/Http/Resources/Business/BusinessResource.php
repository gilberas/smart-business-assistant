<?php

namespace App\Http\Resources\Business;

use App\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'website' => $this->website,
            'logo_url' => $this->logo_url,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'currency' => $this->currency,
            'status' => $this->status,
            'industry' => $this->industry,
            'employee_count' => $this->employee_count,
            'onboarding_step' => $this->onboarding_step,
            'onboarding_completed_at' => $this->onboarding_completed_at?->toISOString(),
            'branding' => $this->branding,
            'preferences' => $this->preferences,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'member_role' => $this->when(
                $this->pivot?->role,
                fn () => $this->pivot->role,
            ),
            'member_status' => $this->when(
                $this->pivot?->status,
                fn () => $this->pivot->status,
            ),
            'settings' => new BusinessSettingsResource($this->whenLoaded('settings')),
            'trial_ends_at' => $this->trial_ends_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
