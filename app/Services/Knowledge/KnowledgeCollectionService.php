<?php

namespace App\Services\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeCollection;
use App\Repositories\Knowledge\KnowledgeCollectionRepository;
use Illuminate\Validation\ValidationException;

class KnowledgeCollectionService
{
    public function __construct(
        private readonly KnowledgeCollectionRepository $collectionRepository,
    ) {}

    public function list(Business $business): iterable
    {
        return $this->collectionRepository->all($business);
    }

    public function get(Business $business, string $uuid): KnowledgeCollection
    {
        $collection = $this->collectionRepository->findByUuid($business, $uuid);
        if (!$collection) {
            throw ValidationException::withMessages([
                'collection' => [__('knowledge.collection_not_found')],
            ]);
        }
        return $collection;
    }

    public function create(Business $business, array $data): KnowledgeCollection
    {
        return $this->collectionRepository->create($business, $data);
    }

    public function update(Business $business, KnowledgeCollection $collection, array $data): KnowledgeCollection
    {
        return $this->collectionRepository->update($collection, $data);
    }

    public function delete(Business $business, KnowledgeCollection $collection): void
    {
        $this->collectionRepository->delete($collection);
    }
}