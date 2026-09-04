<?php

namespace App\Contracts\Broadcast;

use App\Models\Business;
use App\DTOs\Broadcast\AudiencePreviewDTO;

interface AudienceServiceInterface
{
    public function count(Business $business, array $filters): int;

    public function preview(Business $business, array $filters, int $limit = 50): AudiencePreviewDTO;

    public function resolve(Business $business, array $filters): iterable;
}
