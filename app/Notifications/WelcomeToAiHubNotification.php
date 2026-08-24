<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeToAiHubNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ?int $deliveryLogId;

    public function __construct(?int $deliveryLogId = null)
    {
        $this->deliveryLogId = $deliveryLogId;
        $this->afterCommit = true;
    }

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to AI Orbit')
            ->greeting('You’re in, '.$notifiable->name.'!')
            ->line('Your AI Orbit account is ready. Discover AI tools, models, verified benchmarks, pricing intelligence, comparisons, and breaking AI news in one place.')
            ->action('Personalize My AI Orbit', route('account.onboarding'))
            ->line('You can control Breaking News, new model/tool alerts, pricing, benchmarks, followed-entity updates, and the weekly digest from Email Preferences.');
    }
}
