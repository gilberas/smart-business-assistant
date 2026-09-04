<?php

namespace App\Repositories\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeMedium;

class KnowledgeMediaRepository
{
    public function forArticle(Business $business, KnowledgeArticle $article): iterable
    {
        return KnowledgeMedium::where('business_id', $business->id)
            ->where('article_id', $article->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findById(Business $business, int $id): ?KnowledgeMedium
    {
        return KnowledgeMedium::where('business_id', $business->id)->find($id);
    }

    public function findByUuid(Business $business, string $uuid): ?KnowledgeMedium
    {
        return KnowledgeMedium::where('business_id', $business->id)
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(Business $business, array $data): KnowledgeMedium
    {
        $data['business_id'] = $business->id;
        return KnowledgeMedium::create($data);
    }

    public function delete(KnowledgeMedium $media): void
    {
        $media->delete();
    }
}