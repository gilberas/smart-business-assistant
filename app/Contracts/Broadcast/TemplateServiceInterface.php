<?php

namespace App\Contracts\Broadcast;

use App\Models\Business;
use App\Models\WhatsappTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TemplateServiceInterface
{
    public function paginate(Business $business, array $filters, int $perPage): LengthAwarePaginator;

    public function find(Business $business, int $id): ?WhatsappTemplate;

    public function create(Business $business, array $data): WhatsappTemplate;

    public function update(WhatsappTemplate $template, array $data): WhatsappTemplate;

    public function delete(WhatsappTemplate $template): bool;

    public function syncFromMeta(Business $business): int;
}
