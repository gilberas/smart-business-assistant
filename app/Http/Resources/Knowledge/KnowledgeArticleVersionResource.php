<?php

namespace App\Http\Resources\Knowledge;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeArticleVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'article_id' => $this->article_id,
            'version_number' => $this->version_number,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'content_html' => $this->content_html,
            'content_markdown' => $this->content_markdown,
            'change_summary' => $this->change_summary,
            'status' => $this->status,
            'editor_id' => $this->editor_id,
            'editor' => $this->whenLoaded('editor', fn() => \App\Http\Resources\Auth\UserResource::make($this->editor)),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}