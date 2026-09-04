<?php

namespace App\Events\Team;

use App\Models\BusinessInvitation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BusinessInvitation $invitation,
    ) {}
}
