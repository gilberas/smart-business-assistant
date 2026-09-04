<?php

namespace App\Contracts\RAG;

interface VectorDatabaseInterface
{
    public function upsert(string $collection, array $vectors): array;
    public function query(string $collection, array $queryVector, int $topK = 10, array $filters = []): array;
    public function delete(string $collection, array $ids): bool;
    public function collectionExists(string $collection): bool;
    public function createCollection(string $collection, int $dimension): bool;
    public function name(): string;
}
