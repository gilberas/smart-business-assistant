<?php

namespace App\Repositories\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeCollection;

class KnowledgeCollectionRepository
{
    public function all(Business $business): iterable
    {
        return KnowledgeCollection::where('business_id', $business->id)
            ->withCount('articles')
            ->ordered()
            ->get();
    }

    public function findById(Business $business, int $id): ?KnowledgeCollection
    {
        return KnowledgeCollection::where('business_id', $business->id)->find($id);
    }

    public function findByUuid(Business $business, string $uuid): ?KnowledgeCollection
    {
        return KnowledgeCollection::where('business_id', $business->id)
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(Business $business, array $data): KnowledgeCollection
    {
        $data['business_id'] = $business->id;
        return KnowledgeCollection::create($data);
    }

    public function update(KnowledgeCollection $collection, array $data): KnowledgeCollection
    {
        $collection->update($data);
        return $collection->fresh();
    }

    public function delete(KnowledgeCollection $collection): void
    {
        $collection->delete();
    }
}