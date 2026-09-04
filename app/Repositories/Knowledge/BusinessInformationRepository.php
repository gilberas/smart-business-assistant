<?php

namespace App\Repositories\Knowledge;

use App\Models\Business;
use App\Models\BusinessInformation;

class BusinessInformationRepository
{
    public function all(Business $business, array $params = []): iterable
    {
        $query = BusinessInformation::where('business_id', $business->id);

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (!empty($params['is_public'])) {
            $query->where('is_public', filter_var($params['is_public'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->ordered()->get();
    }

    public function findByKey(Business $business, string $key): ?BusinessInformation
    {
        return BusinessInformation::where('business_id', $business->id)
            ->where('key', $key)
            ->first();
    }

    public function findById(Business $business, int $id): ?BusinessInformation
    {
        return BusinessInformation::where('business_id', $business->id)->find($id);
    }

    public function findByUuid(Business $business, string $uuid): ?BusinessInformation
    {
        return BusinessInformation::where('business_id', $business->id)
            ->where('uuid', $uuid)
            ->first();
    }

    public function create(Business $business, array $data): BusinessInformation
    {
        $data['business_id'] = $business->id;
        return BusinessInformation::create($data);
    }

    public function update(BusinessInformation $info, array $data): BusinessInformation
    {
        $info->update($data);
        return $info->fresh();
    }

    public function delete(BusinessInformation $info): void
    {
        $info->delete();
    }

    public function getGroupedByType(Business $business): array
    {
        return BusinessInformation::where('business_id', $business->id)
            ->ordered()
            ->get()
            ->groupBy('type')
            ->toArray();
    }
}