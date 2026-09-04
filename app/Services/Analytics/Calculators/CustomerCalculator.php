<?php

namespace App\Services\Analytics\Calculators;

use App\DTOs\Analytics\AnalyticsFilterDTO;
use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;

class CustomerCalculator extends BaseCalculator
{
    public function summary(Business $business, AnalyticsFilterDTO $filter): array
    {
        return $this->remember($business, 'customer_summary', $filter, function () use ($business, $filter) {
            $query = Customer::where('business_id', $business->id);
            $this->applyDateRange($query, $filter);
            $prevFilter = $this->previousPeriod($filter);

            $total = Customer::where('business_id', $business->id)->count();
            $new = (clone $query)->count();
            $prevNew = Customer::where('business_id', $business->id)
                ->whereBetween('created_at', [$prevFilter->dateRange->startCarbon(), $prevFilter->dateRange->endCarbon()])
                ->count();
            $newChange = $prevNew > 0 ? round((($new - $prevNew) / $prevNew) * 100, 1) : null;

            $interacted = Customer::where('business_id', $business->id)
                ->whereBetween('last_interacted_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()])
                ->count();

            $inactive = Customer::where('business_id', $business->id)
                ->where(function ($q) use ($filter) {
                    $q->whereNull('last_interacted_at')
                      ->orWhere('last_interacted_at', '<', $filter->dateRange->startCarbon());
                })->count();

            $byLifecycle = Customer::where('business_id', $business->id)
                ->select('lifecycle_stage', DB::raw('COUNT(*) as count'))
                ->groupBy('lifecycle_stage')
                ->pluck('count', 'lifecycle_stage');

            $byTier = Customer::where('business_id', $business->id)
                ->select('loyalty_tier', DB::raw('COUNT(*) as count'))
                ->whereNotNull('loyalty_tier')
                ->groupBy('loyalty_tier')
                ->pluck('count', 'loyalty_tier');

            $byCountry = CustomerAddress::whereHas('customer', fn($q) => $q->where('business_id', $business->id))
                ->where('is_primary', true)
                ->select('country', DB::raw('COUNT(DISTINCT customer_id) as count'))
                ->whereNotNull('country')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->pluck('count', 'country');

            $trend = $this->customerTrend($business, $filter);

            $returning = Customer::where('business_id', $business->id)
                ->whereHas('conversations', function ($q) use ($filter) {
                    $q->whereBetween('created_at', [$filter->dateRange->startCarbon(), $filter->dateRange->endCarbon()]);
                })
                ->whereHas('conversations', function ($q) {
                    $q->where('created_at', '<', now()->subMonth());
                })
                ->count();

            return [
                'total' => $total,
                'new' => $new,
                'new_change_percent' => $newChange,
                'interacted' => $interacted,
                'inactive' => $inactive,
                'returning' => $returning,
                'by_lifecycle_stage' => $byLifecycle->toArray(),
                'by_loyalty_tier' => $byTier->toArray(),
                'by_country' => $byCountry->toArray(),
                'trend' => $trend,
            ];
        });
    }

    protected function customerTrend(Business $business, AnalyticsFilterDTO $filter): array
    {
        $query = Customer::where('business_id', $business->id);
        $this->applyDateRange($query, $filter);
        $data = $this->timeSeries($query, 'created_at', $filter->groupBy);

        return [
            'labels' => array_keys($data),
            'datasets' => [['label' => 'New Customers', 'data' => array_values($data)]],
            'type' => 'line',
        ];
    }
}
