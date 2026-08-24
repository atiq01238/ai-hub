<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyAiDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $afterCommit = true;

    public function __construct(
        public array $digest,
        public ?int $deliveryLogId = null,
        public ?string $unsubscribeUrl = null,
    ) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your weekly AI Orbit intelligence digest')
            ->greeting('Your AI week, '.$notifiable->name)
            ->line('Here are the most important additions and updates from AI Orbit this week.');

        foreach ($this->digest['lines'] ?? [] as $line) {
            $mail->line('• '.$line);
        }

        $mail->action('Open AI Orbit', route('home'))
            ->line('You can turn the weekly digest or individual alert categories on/off from Email Preferences.');

        if ($this->unsubscribeUrl) {
            $mail->line('Unsubscribe from intelligence emails: '.$this->unsubscribeUrl);
        }

        return $mail;
    }
}
