<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;


    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify your AI Hub email')
            ->greeting('Welcome to AI Hub, '.$notifiable->name.'!')
            ->line('Confirm your email address to activate account features, alerts, saved items, reviews, and personalized AI intelligence.')
            ->action('Verify email address', $this->verificationUrl($notifiable))
            ->line('This verification link expires in '.config('auth.verification.expire', 60).' minutes.')
            ->line('If you did not create an AI Hub account, you can ignore this email.');
    }
}
