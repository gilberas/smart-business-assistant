<?php

namespace App\Repositories\Broadcast;

use App\Models\Business;
use App\Models\WhatsappTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TemplateRepository
{
    public function paginate(Business $business, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WhatsappTemplate::query()
            ->where('business_id', $business->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        $sortField = $filters['sort_field'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortField, $sortDir);

        return $query->paginate($perPage);
    }

    public function findById(Business $business, int $id): ?WhatsappTemplate
    {
        return WhatsappTemplate::query()
            ->where('business_id', $business->id)
            ->find($id);
    }

    public function findActive(Business $business): iterable
    {
        return WhatsappTemplate::query()
            ->where('business_id', $business->id)
            ->where('status', 'approved')
            ->get();
    }

    public function create(array $data): WhatsappTemplate
    {
        return WhatsappTemplate::query()->create($data);
    }

    public function update(WhatsappTemplate $template, array $data): bool
    {
        return $template->update($data);
    }

    public function delete(WhatsappTemplate $template): bool
    {
        return $template->delete();
    }

    public function upsertByMetaId(Business $business, array $templateData): WhatsappTemplate
    {
        return WhatsappTemplate::query()->updateOrCreate(
            [
                'business_id' => $business->id,
                'meta_template_id' => $templateData['meta_template_id'],
            ],
            $templateData,
        );
    }
}
