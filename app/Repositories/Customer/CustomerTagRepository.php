<?php

namespace App\Repositories\Customer;

use App\Models\Business;
use App\Models\CustomerTag;

class CustomerTagRepository
{
    public function all(Business $business): iterable
    {
        return CustomerTag::where('business_id', $business->id)
            ->withCount('customers')
            ->orderBy('name')
            ->get();
    }

    public function findById(Business $business, int $id): ?CustomerTag
    {
        return CustomerTag::where('business_id', $business->id)->find($id);
    }

    public function findByName(Business $business, string $name): ?CustomerTag
    {
        return CustomerTag::where('business_id', $business->id)
            ->where('name', $name)
            ->first();
    }

    public function create(Business $business, array $data): CustomerTag
    {
        $data['business_id'] = $business->id;
        return CustomerTag::create($data);
    }

    public function update(CustomerTag $tag, array $data): CustomerTag
    {
        $tag->update($data);
        return $tag->fresh();
    }

    public function delete(CustomerTag $tag): void
    {
        $tag->delete();
    }

    public function syncTags(Business $business, array $tagNames): array
    {
        $tagIds = [];
        foreach ($tagNames as $name) {
            $tag = $this->findByName($business, $name);
            if (!$tag) {
                $tag = $this->create($business, ['name' => $name]);
            }
            $tagIds[] = $tag->id;
        }
        return $tagIds;
    }
}
