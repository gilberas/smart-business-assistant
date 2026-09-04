<?php

namespace App\Repositories\AI;

use App\Models\PromptVersion;

class PromptVersionRepository
{
    public function __construct(
        private readonly PromptVersion $model,
    ) {}

    public function create(array $data): PromptVersion
    {
        return $this->model->create($data);
    }

    public function latestVersion(int $templateId): ?PromptVersion
    {
        return $this->model->where('prompt_template_id', $templateId)
            ->orderBy('version', 'desc')
            ->first();
    }
}
