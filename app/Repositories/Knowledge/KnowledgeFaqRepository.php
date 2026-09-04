<?php

namespace App\Repositories\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeFaq;
use Illuminate\Pagination\LengthAwarePaginator;

class KnowledgeFaqRepository
{
    public function paginate(Business $business, array $params = []): LengthAwarePaginator
    {
        $query = KnowledgeFaq::where('business_id', $business->id)
            ->with(['category', 'article']);

        if (!empty($params['is_published'])) {
            $query->where('is_published', filter_var($params['is_published'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        if (!empty($params['search'])) {
            $term = $params['search'];
            $query->where(function ($q) use ($term) {
                $q->where('question', 'like', "%{$term}%")
                  ->orWhere('answer', 'like', "%{$term}%");
            });
        }

        return $query->ordered()->paginate($params['per_page'] ?? 15);
    }

    public function findById(Business $business, int $id): ?KnowledgeFaq
    {
        return KnowledgeFaq::where('business_id', $business->id)->find($id);
    }

    public function findByUuid(Business $business, string $uuid): ?KnowledgeFaq
    {
        return KnowledgeFaq::where('business_id', $business->id)
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(Business $business, array $data): KnowledgeFaq
    {
        $data['business_id'] = $business->id;
        return KnowledgeFaq::create($data);
    }

    public function update(KnowledgeFaq $faq, array $data): KnowledgeFaq
    {
        $faq->update($data);
        return $faq->fresh();
    }

    public function delete(KnowledgeFaq $faq): void
    {
        $faq->delete();
    }
}