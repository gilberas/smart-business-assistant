<?php

namespace App\Events\Customer;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class CustomerArchived
{
    use Dispatchable;

    public function __construct(
        public readonly Customer $customer,
        public readonly ?User $actor = null,
    ) {}
}
