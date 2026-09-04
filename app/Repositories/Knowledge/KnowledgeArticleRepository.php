<?php

namespace App\Repositories\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeArticle;
use Illuminate\Pagination\LengthAwarePaginator;

class KnowledgeArticleRepository
{
    public function paginate(Business $business, array $params = []): LengthAwarePaginator
    {
        $query = KnowledgeArticle::where('business_id', $business->id)
            ->with(['category', 'tags', 'author']);

        if (!empty($params['search'])) {
            $query->search($params['search']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['article_type'])) {
            $query->where('article_type', $params['article_type']);
        }

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        if (!empty($params['visibility'])) {
            $query->where('visibility', $params['visibility']);
        }

        if (!empty($params['tag_ids'])) {
            $tagIds = is_array($params['tag_ids']) ? $params['tag_ids'] : explode(',', $params['tag_ids']);
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('knowledge_tags.id', $tagIds);
            });
        }

        if (!empty($params['author_id'])) {
            $query->where('author_id', $params['author_id']);
        }

        if (!empty($params['is_featured'])) {
            $query->where('is_featured', filter_var($params['is_featured'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }

        if (!empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        $sort = $params['sort'] ?? 'created_at';
        $direction = $params['direction'] ?? 'desc';

        return $query->orderBy($sort, $direction)
            ->paginate($params['per_page'] ?? 15);
    }

    public function findById(Business $business, int $id): ?KnowledgeArticle
    {
        return KnowledgeArticle::where('business_id', $business->id)
            ->with(['category', 'tags', 'author', 'media', 'versions'])
            ->find($id);
    }

    public function findByUuid(Business $business, string $uuid): ?KnowledgeArticle
    {
        return KnowledgeArticle::where('business_id', $business->id)
            ->where('uuid', $uuid)
            ->first();
    }

    public function findBySlug(Business $business, string $slug): ?KnowledgeArticle
    {
        return KnowledgeArticle::where('business_id', $business->id)
            ->where('slug', $slug)
            ->first();
    }

    public function create(Business $business, array $data): KnowledgeArticle
    {
        $data['business_id'] = $business->id;
        return KnowledgeArticle::create($data);
    }

    public function update(KnowledgeArticle $article, array $data): KnowledgeArticle
    {
        $article->update($data);
        return $article->fresh();
    }

    public function delete(KnowledgeArticle $article): void
    {
        $article->delete();
    }

    public function forceDelete(KnowledgeArticle $article): void
    {
        $article->forceDelete();
    }

    public function countByStatus(Business $business): array
    {
        return KnowledgeArticle::where('business_id', $business->id)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function countByType(Business $business): array
    {
        return KnowledgeArticle::where('business_id', $business->id)
            ->selectRaw("article_type, COUNT(*) as count")
            ->groupBy('article_type')
            ->pluck('count', 'article_type')
            ->toArray();
    }

    public function incrementViewCount(KnowledgeArticle $article): void
    {
        $article->increment('view_count');
    }

    public function markHelpful(KnowledgeArticle $article): void
    {
        $article->increment('helpful_count');
    }

    public function markNotHelpful(KnowledgeArticle $article): void
    {
        $article->increment('not_helpful_count');
    }
}