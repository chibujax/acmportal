<?php

namespace App\Notifications;

use App\Models\PendingMember;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        private PendingMember $member,
        private string $registrationUrl
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You\'re invited to join ' . config('app.name'))
            ->greeting("Dear {$this->member->name},")
            ->line('You have been invited to join the Abia Community Manchester member portal.')
            ->line('Please click the button below to create your account. The link expires in ' . config('app.registration_token_expiry', 7) . ' days.')
            ->action('Create My Account', $this->registrationUrl)
            ->line('If you did not expect this invitation, you can safely ignore this email.')
            ->salutation('Abia Community Manchester');
    }
}
