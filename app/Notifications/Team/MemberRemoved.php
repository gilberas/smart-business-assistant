<?php

namespace App\Notifications\Team;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberRemoved extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Business $business,
        public readonly User $removedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('team.removed_subject', ['business' => $this->business->name]))
            ->greeting(__('team.hello', ['name' => $notifiable->name]))
            ->line(__('team.removed_intro', [
                'business' => $this->business->name,
                'removed_by' => $this->removedBy->name,
            ]))
            ->line(__('team.removed_footer'));
    }
}
