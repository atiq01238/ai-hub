<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = AppNotification::where('user_id', auth()->id())
            ->orWhereNull('user_id')
            ->latest()
            ->paginate(30);

        $unreadCount = AppNotification::where(fn ($q) => $q->where('user_id', auth()->id())->orWhereNull('user_id'))
            ->unread()
            ->count();

        return view('system.notifications', compact('notifications', 'unreadCount'));
    }

    public function markRead(int $id)
    {
        AppNotification::findOrFail($id)->update(['read_at' => now()]);

        return redirect()->back();
    }

    public function markAllRead()
    {
        AppNotification::where(fn ($q) => $q->where('user_id', auth()->id())->orWhereNull('user_id'))
            ->unread()
            ->update(['read_at' => now()]);

        return redirect()->back()->with('status', 'All caught up.');
    }

    public function destroy(int $id)
    {
        AppNotification::findOrFail($id)->delete();

        return redirect()->back();
    }
}
