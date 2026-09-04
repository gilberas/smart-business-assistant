<?php

namespace App\Repositories\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeCategory;

class KnowledgeCategoryRepository
{
    public function all(Business $business, array $params = []): iterable
    {
        $query = KnowledgeCategory::where('business_id', $business->id)
            ->withCount('articles');

        if (!empty($params['is_active'])) {
            $query->where('is_active', filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($params['parent_id'])) {
            $query->where('parent_id', $params['parent_id']);
        }

        return $query->ordered()->get();
    }

    public function tree(Business $business): iterable
    {
        return KnowledgeCategory::where('business_id', $business->id)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->ordered()->withCount('articles');
            }])
            ->withCount('articles')
            ->ordered()
            ->get();
    }

    public function findById(Business $business, int $id): ?KnowledgeCategory
    {
        return KnowledgeCategory::where('business_id', $business->id)->find($id);
    }

    public function findByUuid(Business $business, string $uuid): ?KnowledgeCategory
    {
        return KnowledgeCategory::where('business_id', $business->id)
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(Business $business, array $data): KnowledgeCategory
    {
        $data['business_id'] = $business->id;
        return KnowledgeCategory::create($data);
    }

    public function update(KnowledgeCategory $category, array $data): KnowledgeCategory
    {
        $category->update($data);
        return $category->fresh();
    }

    public function delete(KnowledgeCategory $category): void
    {
        $category->delete();
    }
}