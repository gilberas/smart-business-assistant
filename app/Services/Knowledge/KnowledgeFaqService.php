<?php

namespace App\Services\Knowledge;

use App\Models\Business;
use App\Models\KnowledgeFaq;
use App\Repositories\Knowledge\KnowledgeFaqRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class KnowledgeFaqService
{
    public function __construct(
        private readonly KnowledgeFaqRepository $faqRepository,
    ) {}

    public function list(Business $business, array $params = []): LengthAwarePaginator
    {
        return $this->faqRepository->paginate($business, $params);
    }

    public function get(Business $business, string $uuid): KnowledgeFaq
    {
        $faq = $this->faqRepository->findByUuid($business, $uuid);
        if (!$faq) {
            throw ValidationException::withMessages([
                'faq' => [__('knowledge.faq_not_found')],
            ]);
        }
        return $faq;
    }

    public function create(Business $business, array $data): KnowledgeFaq
    {
        return $this->faqRepository->create($business, $data);
    }

    public function update(Business $business, KnowledgeFaq $faq, array $data): KnowledgeFaq
    {
        return $this->faqRepository->update($faq, $data);
    }

    public function delete(Business $business, KnowledgeFaq $faq): void
    {
        $this->faqRepository->delete($faq);
    }
}