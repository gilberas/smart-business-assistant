<?php

namespace App\Repositories\Customer;

use App\Models\Business;
use App\Models\Customer;
use App\Models\CustomerActivity;

class CustomerActivityRepository
{
    public function forCustomer(Business $business, Customer $customer, int $limit = 50): iterable
    {
        return CustomerActivity::where('business_id', $business->id)
            ->where('customer_id', $customer->id)
            ->with('actor')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
