<?php

namespace App\Events\Team;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OwnershipTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly User $previousOwner,
        public readonly User $newOwner,
        public readonly User $transferredBy,
    ) {}
}
