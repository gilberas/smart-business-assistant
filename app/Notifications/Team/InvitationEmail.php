<?php

namespace App\Notifications\Team;

use App\Models\BusinessInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationEmail extends Notification
{
    use Queueable;

    public function __construct(
        public readonly BusinessInvitation $invitation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $business = $this->invitation->business;
        $inviter = $this->invitation->inviter;
        $acceptUrl = config('app.frontend_url') . "/invitations/{$this->invitation->token}/accept";

        return (new MailMessage)
            ->subject(__('team.invitation_subject', ['business' => $business->name]))
            ->greeting(__('team.invitation_greeting', ['name' => $notifiable->name ?? $this->invitation->email]))
            ->line(__('team.invitation_intro', [
                'inviter' => $inviter->name,
                'business' => $business->name,
            ]))
            ->line(__('team.invitation_role_info', ['role' => __('roles.' . $this->invitation->role)]))
            ->action(__('team.invitation_accept_button'), $acceptUrl)
            ->line(__('team.invitation_expires', ['date' => $this->invitation->expires_at->format('F j, Y')]))
            ->line(__('team.invitation_ignore'));
    }
}
