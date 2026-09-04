<?php

namespace App\Events\Team;

use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BusinessInvitation $invitation,
    ) {}
}
