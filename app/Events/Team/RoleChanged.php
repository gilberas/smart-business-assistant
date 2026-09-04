<?php

namespace App\Events\Team;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Business $business,
        public readonly User $member,
        public readonly User $changedBy,
        public readonly string $oldRole,
        public readonly string $newRole,
    ) {}
}
