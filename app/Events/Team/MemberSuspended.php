<?php

namespace App\Events\Team;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberSuspended
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly User $member,
        public readonly User $suspendedBy,
    ) {}
}
