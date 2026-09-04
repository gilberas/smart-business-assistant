<?php

namespace App\Services\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeCategory;
use App\Repositories\Knowledge\KnowledgeCategoryRepository;
use Illuminate\Validation\ValidationException;

class KnowledgeCategoryService
{
    public function __construct(
        private readonly KnowledgeCategoryRepository $categoryRepository,
    ) {}

    public function list(Business $business, array $params = []): iterable
    {
        return $this->categoryRepository->all($business, $params);
    }

    public function tree(Business $business): iterable
    {
        return $this->categoryRepository->tree($business);
    }

    public function get(Business $business, string $uuid): KnowledgeCategory
    {
        $category = $this->categoryRepository->findByUuid($business, $uuid);
        if (!$category) {
            throw ValidationException::withMessages([
                'category' => [__('knowledge.category_not_found')],
            ]);
        }
        return $category;
    }

    public function create(Business $business, array $data): KnowledgeCategory
    {
        return $this->categoryRepository->create($business, $data);
    }

    public function update(Business $business, KnowledgeCategory $category, array $data): KnowledgeCategory
    {
        return $this->categoryRepository->update($category, $data);
    }

    public function delete(Business $business, KnowledgeCategory $category): void
    {
        if ($category->articles()->count() > 0) {
            throw ValidationException::withMessages([
                'category' => [__('knowledge.category_has_articles')],
            ]);
        }
        $this->categoryRepository->delete($category);
    }
}