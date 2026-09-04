<?php

namespace App\DTOs\Broadcast;

class AudiencePreviewDTO
{
    public function __construct(
        public readonly int $totalCount,
        public readonly array $previewCustomers,
        public readonly array $appliedFilters,
    ) {}

    public static function fromResult(int $count, array $preview, array $filters): self
    {
        return new self(
            totalCount: $count,
            previewCustomers: $preview,
            appliedFilters: $filters,
        );
    }
}
