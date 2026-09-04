<?php

namespace App\Events\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerMerged
{
    use Dispatchable;

    public function __construct(
        public readonly Customer $primaryCustomer,
        public readonly Customer $mergedCustomer,
        public readonly ?User $actor = null,
    ) {}
}
