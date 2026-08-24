<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IntelligenceEmailAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public $afterCommit = true;

    public function __construct(
        public string $subjectLine,
        public string $heading,
        public string $message,
        public string $actionLabel,
        public string $actionUrl,
        public ?int $deliveryLogId = null,
        public ?string $unsubscribeUrl = null,
    ) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting($this->heading)
            ->line($this->message)
            ->action($this->actionLabel, $this->actionUrl)
            ->line('Manage what AI Orbit emails you receive from My AI Orbit → Email Preferences.');

        if ($this->unsubscribeUrl) {
            $mail->line('Unsubscribe from intelligence emails: '.$this->unsubscribeUrl);
        }

        return $mail;
    }
}
