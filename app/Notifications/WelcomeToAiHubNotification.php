<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeToAiHubNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $afterCommit = true;

    public ?int $deliveryLogId;

    public function __construct(?int $deliveryLogId = null)
    {
        $this->deliveryLogId = $deliveryLogId;
    }

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to AI Hub')
            ->greeting('You’re in, '.$notifiable->name.'!')
            ->line('Your AI Hub account is ready. Discover AI tools, models, verified benchmarks, pricing intelligence, comparisons, and breaking AI news in one place.')
            ->action('Personalize My AI Hub', route('account.onboarding'))
            ->line('You can control Breaking News, new model/tool alerts, pricing, benchmarks, followed-entity updates, and the weekly digest from Email Preferences.');
    }
}
