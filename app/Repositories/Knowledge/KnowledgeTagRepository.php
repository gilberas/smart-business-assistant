<?php

namespace App\Repositories\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeTag;

class KnowledgeTagRepository
{
    public function all(Business $business, array $params = []): iterable
    {
        $query = KnowledgeTag::where('business_id', $business->id)
            ->withCount('articles');

        if (!empty($params['search'])) {
            $query->where('name', 'like', "%{$params['search']}%");
        }

        return $query->ordered()->get();
    }

    public function findById(Business $business, int $id): ?KnowledgeTag
    {
        return KnowledgeTag::where('business_id', $business->id)->find($id);
    }

    public function findByName(Business $business, string $name): ?KnowledgeTag
    {
        return KnowledgeTag::where('business_id', $business->id)
            ->where('name', $name)
            ->first();
    }

    public function create(Business $business, array $data): KnowledgeTag
    {
        $data['business_id'] = $business->id;
        return KnowledgeTag::create($data);
    }

    public function update(KnowledgeTag $tag, array $data): KnowledgeTag
    {
        $tag->update($data);
        return $tag->fresh();
    }

    public function delete(KnowledgeTag $tag): void
    {
        $tag->delete();
    }

    public function syncTags(Business $business, array $tagNames): array
    {
        $tagIds = [];

        foreach ($tagNames as $name) {
            $tag = $this->findByName($business, $name);

            if (!$tag) {
                $tag = $this->create($business, ['name' => $name]);
            }

            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }
}