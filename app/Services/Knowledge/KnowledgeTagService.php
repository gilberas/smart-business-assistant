<?php

namespace App\Services\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeTag;
use App\Repositories\Knowledge\KnowledgeTagRepository;
use Illuminate\Validation\ValidationException;

class KnowledgeTagService
{
    public function __construct(
        private readonly KnowledgeTagRepository $tagRepository,
    ) {}

    public function list(Business $business): iterable
    {
        return $this->tagRepository->all($business);
    }

    public function get(Business $business, int $id): KnowledgeTag
    {
        $tag = $this->tagRepository->findById($business, $id);
        if (!$tag) {
            throw ValidationException::withMessages([
                'tag' => [__('knowledge.tag_not_found')],
            ]);
        }
        return $tag;
    }

    public function create(Business $business, array $data): KnowledgeTag
    {
        $existing = $this->tagRepository->findByName($business, $data['name']);
        if ($existing) {
            throw ValidationException::withMessages([
                'name' => [__('knowledge.tag_duplicate')],
            ]);
        }
        return $this->tagRepository->create($business, $data);
    }

    public function update(Business $business, KnowledgeTag $tag, array $data): KnowledgeTag
    {
        if (!empty($data['name']) && $data['name'] !== $tag->name) {
            $existing = $this->tagRepository->findByName($business, $data['name']);
            if ($existing && $existing->id !== $tag->id) {
                throw ValidationException::withMessages([
                    'name' => [__('knowledge.tag_duplicate')],
                ]);
            }
        }
        return $this->tagRepository->update($tag, $data);
    }

    public function delete(Business $business, KnowledgeTag $tag): void
    {
        $this->tagRepository->delete($tag);
    }
}