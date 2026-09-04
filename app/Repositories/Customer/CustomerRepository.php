<?php

namespace App\Repositories\Customer;

use App\Models\Business;
use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository
{
    public function paginate(Business $business, array $params = []): LengthAwarePaginator
    {
        $query = Customer::where('business_id', $business->id)
            ->with(['tags', 'primaryAddress']);

        if (!empty($params['search'])) {
            $query->search($params['search']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['lifecycle_stage'])) {
            $query->where('lifecycle_stage', $params['lifecycle_stage']);
        }

        if (!empty($params['source'])) {
            $query->where('source', $params['source']);
        }

        if (!empty($params['customer_type'])) {
            $query->where('customer_type', $params['customer_type']);
        }

        if (!empty($params['tag_ids'])) {
            $tagIds = is_array($params['tag_ids']) ? $params['tag_ids'] : explode(',', $params['tag_ids']);
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('customer_tags.id', $tagIds);
            });
        }

        if (!empty($params['assigned_to'])) {
            $query->where('assigned_to', $params['assigned_to']);
        }

        if (!empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }

        if (!empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        $sort = $params['sort'] ?? 'created_at';
        $direction = $params['direction'] ?? 'desc';

        return $query->orderBy($sort, $direction)
            ->paginate($params['per_page'] ?? 15);
    }

    public function findById(Business $business, int $id): ?Customer
    {
        return Customer::where('business_id', $business->id)
            ->with(['tags', 'addresses', 'primaryAddress', 'customFieldValues.customField'])
            ->find($id);
    }

    public function findByUuid(Business $business, string $uuid): ?Customer
    {
        return Customer::where('business_id', $business->id)
            ->where('uuid', $uuid)
            ->first();
    }

    public function findByPhone(Business $business, string $phone): ?Customer
    {
        return Customer::where('business_id', $business->id)
            ->where(function ($q) use ($phone) {
                $q->where('phone', $phone)->orWhere('phone_alt', $phone);
            })
            ->first();
    }

    public function findByEmail(Business $business, string $email): ?Customer
    {
        return Customer::where('business_id', $business->id)
            ->where('email', $email)
            ->first();
    }

    public function create(Business $business, array $data): Customer
    {
        $data['business_id'] = $business->id;

        if (!isset($data['source'])) {
            $data['source'] = 'manual';
        }

        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer->fresh();
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function restore(Customer $customer): void
    {
        $customer->restore();
    }

    public function forceDelete(Customer $customer): void
    {
        $customer->forceDelete();
    }

    public function countByStatus(Business $business): array
    {
        return Customer::where('business_id', $business->id)
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function countByStage(Business $business): array
    {
        return Customer::where('business_id', $business->id)
            ->selectRaw("lifecycle_stage, COUNT(*) as count")
            ->groupBy('lifecycle_stage')
            ->pluck('count', 'lifecycle_stage')
            ->toArray();
    }

    public function all(Business $business): iterable
    {
        return Customer::where('business_id', $business->id)
            ->orderBy('name')
            ->get();
    }
}
