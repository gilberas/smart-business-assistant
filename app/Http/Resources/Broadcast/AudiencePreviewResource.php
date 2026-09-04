<?php

namespace App\Http\Resources\Broadcast;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudiencePreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_count' => $this->totalCount,
            'preview_customers' => $this->previewCustomers,
            'applied_filters' => $this->appliedFilters,
        ];
    }
}
