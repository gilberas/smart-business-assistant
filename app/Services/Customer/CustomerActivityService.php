<?php

namespace App\Services\Customer;

use App\Models\Business;
use App\Models\Customer;
use App\Repositories\Customer\CustomerActivityRepository;

class CustomerActivityService
{
    public function __construct(
        private readonly CustomerActivityRepository $activityRepository,
    ) {}

    public function forCustomer(Business $business, Customer $customer, int $limit = 50): iterable
    {
        return $this->activityRepository->forCustomer($business, $customer, $limit);
    }
}
