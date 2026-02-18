<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailVerificationNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Generate/replace verification token
        $token = Str::random(64);

        DB::table('email_verifications')->where('user_id', $notifiable->id)->delete();
        DB::table('email_verifications')->insert([
            'user_id'    => $notifiable->id,
            'token'      => $token,
            'expires_at' => now()->addHours(48),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $url = route('email.verify', $token);

        return (new MailMessage)
            ->subject('Verify your email – ' . config('app.name'))
            ->greeting("Hello {$notifiable->name},")
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email', $url)
            ->line('This link expires in 48 hours. If you did not register, no action is needed.');
    }
}
