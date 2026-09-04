<?php

namespace App\Repositories\AI;

use App\Models\PromptTemplate;
use Illuminate\Database\Eloquent\Collection;

class PromptTemplateRepository
{
    public function __construct(
        private readonly PromptTemplate $model,
    ) {}

    public function all(?int $businessId = null): Collection
    {
        $query = $this->model->with('versions')->orderBy('name');
        if ($businessId) {
            $query->where('business_id', $businessId);
        }
        return $query->get();
    }

    public function findById(int $id): ?PromptTemplate
    {
        return $this->model->with('versions')->find($id);
    }

    public function findBySlug(int $businessId, string $slug): ?PromptTemplate
    {
        return $this->model->where('business_id', $businessId)->where('slug', $slug)->first();
    }

    public function findByType(int $businessId, string $type): Collection
    {
        return $this->model->where('business_id', $businessId)->ofType($type)->active()->get();
    }

    public function create(array $data): PromptTemplate
    {
        return $this->model->create($data);
    }

    public function update(PromptTemplate $template, array $data): bool
    {
        return $template->update($data);
    }

    public function delete(PromptTemplate $template): bool
    {
        return $template->delete();
    }
}
