<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReplyMail;
use App\Models\AppNotification;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::query()->with(['user', 'handler']);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['new', 'read', 'replied', 'closed', 'spam'], true)) {
                $query->where('status', $status);
            }
        }

        if ($topic = $request->query('topic')) {
            if (in_array($topic, ['general', 'feedback', 'data_correction', 'partnership', 'press', 'technical'], true)) {
                $query->where('topic', $topic);
            }
        }

        $counts = [
            'all' => ContactMessage::count(),
            'new' => ContactMessage::where('status', 'new')->count(),
            'read' => ContactMessage::where('status', 'read')->count(),
            'replied' => ContactMessage::where('status', 'replied')->count(),
            'closed' => ContactMessage::where('status', 'closed')->count(),
            'spam' => ContactMessage::where('status', 'spam')->count(),
        ];

        $messages = $query
            ->orderByRaw("CASE status WHEN 'new' THEN 1 WHEN 'read' THEN 2 WHEN 'replied' THEN 3 WHEN 'closed' THEN 4 ELSE 5 END")
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('community.contact-messages.index', compact('messages', 'counts'));
    }

    public function show(ContactMessage $contactMessage)
    {
        if ($contactMessage->status === 'new') {
            $contactMessage->forceFill([
                'status' => 'read',
                'read_at' => $contactMessage->read_at ?: now(),
                'handled_by' => $contactMessage->handled_by ?: auth()->id(),
            ])->save();
        }

        AppNotification::query()
            ->where('user_id', auth()->id())
            ->where('type', 'contact_message')
            ->where('action_url', route('admin.contact-messages.show', $contactMessage))
            ->unread()
            ->update(['read_at' => now()]);

        $contactMessage->load(['user', 'handler', 'replies.admin']);

        return view('community.contact-messages.show', compact('contactMessage'));
    }

    public function updateStatus(Request $request, ContactMessage $contactMessage)
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,read,replied,closed,spam'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $now = now();
        $status = $data['status'];

        $contactMessage->forceFill([
            'status' => $status,
            'admin_notes' => $data['admin_notes'] ?? null,
            'handled_by' => auth()->id(),
            'read_at' => in_array($status, ['read', 'replied', 'closed', 'spam'], true)
                ? ($contactMessage->read_at ?: $now)
                : null,
            'replied_at' => $status === 'replied'
                ? ($contactMessage->replied_at ?: $now)
                : ($status === 'closed' ? $contactMessage->replied_at : null),
            'closed_at' => $status === 'closed' ? ($contactMessage->closed_at ?: $now) : null,
            'spam_at' => $status === 'spam' ? ($contactMessage->spam_at ?: $now) : null,
        ])->save();

        return back()->with('status', 'Contact message workflow updated.');
    }

    public function reply(Request $request, ContactMessage $contactMessage)
    {
        $data = $request->validate([
            'reply_message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $reply = ContactMessageReply::create([
            'contact_message_id' => $contactMessage->id,
            'admin_id' => auth()->id(),
            'body' => $data['reply_message'],
        ]);

        try {
            Mail::to($contactMessage->email)->queue(new ContactMessageReplyMail($contactMessage, $reply));
        } catch (\Throwable $exception) {
            report($exception);
            $reply->delete();

            return back()->withErrors([
                'reply_message' => 'The reply could not be queued. Your message was not marked as replied.',
            ])->withInput();
        }

        $contactMessage->forceFill([
            'status' => 'replied',
            'handled_by' => auth()->id(),
            'read_at' => $contactMessage->read_at ?: now(),
            'replied_at' => now(),
            'closed_at' => null,
            'spam_at' => null,
        ])->save();

        return back()->with('status', 'Reply queued for ' . $contactMessage->email . '.');
    }
}
