<?php

namespace App\Notifications\Team;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoleUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Business $business,
        public readonly User $changedBy,
        public readonly string $oldRole,
        public readonly string $newRole,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('team.role_updated_subject', ['business' => $this->business->name]))
            ->greeting(__('team.hello', ['name' => $notifiable->name]))
            ->line(__('team.role_updated_intro', [
                'business' => $this->business->name,
                'changed_by' => $this->changedBy->name,
            ]))
            ->line(__('team.role_updated_detail', [
                'old_role' => __('roles.' . $this->oldRole),
                'new_role' => __('roles.' . $this->newRole),
            ]))
            ->action(__('team.view_team'), config('app.frontend_url') . '/team');
    }
}
