<?php

namespace App\Services\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeMedium;
use App\Repositories\Knowledge\KnowledgeMediaRepository;
use Illuminate\Validation\ValidationException;

class KnowledgeMediaService
{
    public function __construct(
        private readonly KnowledgeMediaRepository $mediaRepository,
    ) {}

    public function forArticle(Business $business, KnowledgeArticle $article): iterable
    {
        return $this->mediaRepository->forArticle($business, $article);
    }

    public function get(Business $business, string $uuid): KnowledgeMedium
    {
        $media = $this->mediaRepository->findByUuid($business, $uuid);
        if (!$media) {
            throw ValidationException::withMessages([
                'media' => [__('knowledge.media_not_found')],
            ]);
        }
        return $media;
    }

    public function create(Business $business, array $data): KnowledgeMedium
    {
        return $this->mediaRepository->create($business, $data);
    }

    public function delete(Business $business, KnowledgeMedium $media): void
    {
        $this->mediaRepository->delete($media);
    }
}