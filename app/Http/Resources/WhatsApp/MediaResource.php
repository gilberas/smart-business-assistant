<?php

namespace App\Http\Resources\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'media_id' => $this->media_id,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_name' => $this->file_name,
            'caption' => $this->caption,
            'size_for_humans' => $this->getSizeForHumans(),
            'url' => $this->when($request->routeIs('*.media.download'), function () {
                return $this->temporary_url;
            }),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
