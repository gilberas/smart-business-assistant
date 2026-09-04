<?php

namespace App\Http\Resources\Knowledge;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeArticleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'article_type' => $this->article_type,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'featured_image' => $this->featured_image,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toISOString(),
            'view_count' => $this->view_count,
            'helpful_count' => $this->helpful_count,
            'category' => $this->whenLoaded('category', fn() => new KnowledgeCategoryResource($this->category)),
            'tags' => $this->whenLoaded('tags', fn() => KnowledgeTagResource::collection($this->tags)),
            'author' => $this->whenLoaded('author', fn() => [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}