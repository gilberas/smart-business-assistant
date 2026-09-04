<?php

namespace App\Services\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeArticle;
use App\Models\User;
use App\Repositories\Knowledge\KnowledgeArticleRepository;
use App\Repositories\Knowledge\KnowledgeTagRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class KnowledgeArticleService
{
    public function __construct(
        private readonly KnowledgeArticleRepository $articleRepository,
        private readonly KnowledgeTagRepository $tagRepository,
    ) {}

    public function list(Business $business, array $params = []): LengthAwarePaginator
    {
        return $this->articleRepository->paginate($business, $params);
    }

    public function get(Business $business, string $uuid): KnowledgeArticle
    {
        $article = $this->articleRepository->findByUuid($business, $uuid);
        if (!$article) {
            throw ValidationException::withMessages([
                'article' => [__('knowledge.article_not_found')],
            ]);
        }
        return $article;
    }

    public function create(Business $business, User $user, array $data): KnowledgeArticle
    {
        $data['author_id'] = $user->id;

        if (!empty($data['status']) && $data['status'] === 'published') {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        $article = $this->articleRepository->create($business, $data);

        if (!empty($data['tag_ids'])) {
            $article->tags()->sync($data['tag_ids']);
        }

        if (!empty($data['tags'])) {
            $tagIds = $this->tagRepository->syncTags($business, $data['tags']);
            $article->tags()->sync($tagIds);
        }

        return $article->load(['category', 'tags', 'author']);
    }

    public function update(Business $business, User $user, KnowledgeArticle $article, array $data): KnowledgeArticle
    {
        if (!empty($data['status']) && $data['status'] === 'published' && !$article->published_at) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        $article = $this->articleRepository->update($article, $data);

        if (isset($data['tag_ids'])) {
            $article->tags()->sync($data['tag_ids']);
        }

        if (isset($data['tags'])) {
            $tagIds = $this->tagRepository->syncTags($business, $data['tags']);
            $article->tags()->sync($tagIds);
        }

        return $article->load(['category', 'tags', 'author']);
    }

    public function delete(Business $business, KnowledgeArticle $article): void
    {
        $this->articleRepository->delete($article);
    }

    public function publish(Business $business, User $user, KnowledgeArticle $article): KnowledgeArticle
    {
        return $this->articleRepository->update($article, [
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
        ]);
    }

    public function archive(Business $business, KnowledgeArticle $article): KnowledgeArticle
    {
        return $this->articleRepository->update($article, [
            'status' => 'archived',
        ]);
    }

    public function restore(Business $business, KnowledgeArticle $article): KnowledgeArticle
    {
        return $this->articleRepository->update($article, [
            'status' => 'draft',
        ]);
    }

    public function search(Business $business, string $term, int $limit = 10): iterable
    {
        return KnowledgeArticle::where('business_id', $business->id)
            ->where('status', 'published')
            ->search($term)
            ->limit($limit)
            ->get(['id', 'uuid', 'title', 'slug', 'excerpt', 'article_type', 'published_at']);
    }

    public function trackView(Business $business, KnowledgeArticle $article): void
    {
        $this->articleRepository->incrementViewCount($article);
    }

    public function markHelpful(Business $business, KnowledgeArticle $article): void
    {
        $this->articleRepository->markHelpful($article);
    }

    public function markNotHelpful(Business $business, KnowledgeArticle $article): void
    {
        $this->articleRepository->markNotHelpful($article);
    }

    public function stats(Business $business): array
    {
        return [
            'total' => KnowledgeArticle::where('business_id', $business->id)->count(),
            'published' => KnowledgeArticle::where('business_id', $business->id)->where('status', 'published')->count(),
            'drafts' => KnowledgeArticle::where('business_id', $business->id)->where('status', 'draft')->count(),
            'archived' => KnowledgeArticle::where('business_id', $business->id)->where('status', 'archived')->count(),
            'by_type' => $this->articleRepository->countByType($business),
            'by_status' => $this->articleRepository->countByStatus($business),
        ];
    }
}