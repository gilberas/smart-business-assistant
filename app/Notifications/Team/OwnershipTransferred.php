<?php

namespace App\Notifications\Team;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnershipTransferred extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Business $business,
        public readonly User $previousOwner,
        public readonly User $newOwner,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isNewOwner = $notifiable->id === $this->newOwner->id;

        if ($isNewOwner) {
            return (new MailMessage)
                ->subject(__('team.ownership_received_subject', ['business' => $this->business->name]))
                ->greeting(__('team.hello', ['name' => $notifiable->name]))
                ->line(__('team.ownership_received_intro', ['business' => $this->business->name]))
                ->action(__('team.view_team'), config('app.frontend_url') . '/team');
        }

        return (new MailMessage)
            ->subject(__('team.ownership_transferred_subject', ['business' => $this->business->name]))
            ->greeting(__('team.hello', ['name' => $notifiable->name]))
            ->line(__('team.ownership_transferred_intro', [
                'business' => $this->business->name,
                'new_owner' => $this->newOwner->name,
            ]));
    }
}
