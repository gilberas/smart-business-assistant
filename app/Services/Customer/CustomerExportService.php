<?php

namespace App\Services\Customer;

use App\Enums\ActivityType;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerActivity;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerExportService
{
    public function export(Business $business, User $user, array $params = []): Collection
    {
        $query = Customer::where('business_id', $business->id)
            ->with(['tags', 'primaryAddress']);

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['lifecycle_stage'])) {
            $query->where('lifecycle_stage', $params['lifecycle_stage']);
        }

        if (!empty($params['source'])) {
            $query->where('source', $params['source']);
        }

        if (!empty($params['date_from'])) {
            $query->whereDate('created_at', '>=', $params['date_from']);
        }

        if (!empty($params['date_to'])) {
            $query->whereDate('created_at', '<=', $params['date_to']);
        }

        if (!empty($params['ids'])) {
            $ids = is_array($params['ids']) ? $params['ids'] : explode(',', $params['ids']);
            $query->whereIn('id', $ids);
        }

        $customers = $query->orderBy('created_at', 'desc')->get();

        $exportData = $customers->map(fn(Customer $customer) => [
            'uuid' => $customer->uuid,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'status' => $customer->status,
            'source' => $customer->source,
            'customer_type' => $customer->customer_type,
            'lifecycle_stage' => $customer->lifecycle_stage,
            'company_name' => $customer->company_name,
            'job_title' => $customer->job_title,
            'website_url' => $customer->website_url,
            'tags' => $customer->tags->pluck('name')->implode(', '),
            'city' => $customer->primaryAddress?->city,
            'country' => $customer->primaryAddress?->country,
            'total_orders' => $customer->total_orders,
            'total_spent' => $customer->total_spent,
            'loyalty_points' => $customer->loyalty_points,
            'created_at' => $customer->created_at->toISOString(),
            'last_interacted_at' => $customer->last_interacted_at?->toISOString(),
        ]);

        CustomerActivity::record(
            $business->id,
            0,
            ActivityType::Exported,
            $user->id,
            __('customers.activity_exported', ['count' => $customers->count()]),
        );

        return $exportData;
    }

    public function exportHeaders(): array
    {
        return [
            'uuid', 'name', 'email', 'phone', 'status', 'source',
            'customer_type', 'lifecycle_stage', 'company_name', 'job_title',
            'website_url', 'tags', 'city', 'country',
            'total_orders', 'total_spent', 'loyalty_points',
            'created_at', 'last_interacted_at',
        ];
    }
}
