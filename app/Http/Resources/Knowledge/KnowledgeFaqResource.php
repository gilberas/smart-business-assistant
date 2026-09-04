<?php

namespace App\Http\Resources\Knowledge;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KnowledgeFaqResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'question' => $this->question,
            'answer' => $this->answer,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'category' => $this->whenLoaded('category', fn() => new KnowledgeCategoryResource($this->category)),
            'article' => $this->whenLoaded('article', fn() => [
                'id' => $this->article->id,
                'uuid' => $this->article->uuid,
                'title' => $this->article->title,
                'slug' => $this->article->slug,
            ]),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}