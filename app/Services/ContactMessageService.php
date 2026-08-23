<?php

namespace App\Services;

use App\Mail\ContactMessageReceivedMail;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ContactMessageService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * Notify administrators without ever allowing a secondary notification
     * channel to make the public contact form fail.
     */
    public function notifyAdmins(ContactMessage $message): void
    {
        $admins = $this->authorizedAdmins();
        $url = route('admin.contact-messages.show', $message);
        $topic = Str::headline($message->topic ?: 'general');
        $description = $message->name . ' · ' . $topic . ' · ' . Str::limit($message->subject, 100);

        try {
            foreach ($admins as $admin) {
                $this->notifications->user(
                    (int) $admin->id,
                    'New contact message',
                    $description,
                    $url,
                    'mail',
                    'info',
                    'contact_message'
                );
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        try {
            foreach ($this->mailRecipients($admins) as $email) {
                Mail::to($email)->queue(new ContactMessageReceivedMail($message));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function authorizedAdmins(): Collection
    {
        return User::query()
            ->with(['roleModel.permissions'])
            ->where('role', 'admin')
            ->where('status', 'active')
            ->whereNotNull('email')
            ->get()
            ->filter(fn (User $user) => $user->canAccessModule('Users', 'View'))
            ->values();
    }

    private function mailRecipients(Collection $admins): Collection
    {
        $configured = collect(preg_split('/[,;]+/', (string) config('mail.contact_to')))
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($configured->isNotEmpty()) {
            return $configured;
        }

        return $admins->pluck('email')->filter()->unique()->values();
    }
}
