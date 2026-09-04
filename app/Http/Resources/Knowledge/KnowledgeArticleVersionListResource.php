<?php

namespace App\Http\Resources\Knowledge;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeArticleVersionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'version_number' => $this->version_number,
            'title' => $this->title,
            'slug' => $this->slug,
            'change_summary' => $this->change_summary,
            'status' => $this->status,
            'editor_id' => $this->editor_id,
            'editor' => $this->whenLoaded('editor', fn() => [
                'id' => $this->editor->id,
                'name' => $this->editor->name,
            ]),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}