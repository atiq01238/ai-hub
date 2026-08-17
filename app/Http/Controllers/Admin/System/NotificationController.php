<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = $this->visibleNotifications()
            ->latest()
            ->paginate(30);

        $unreadCount = $this->visibleNotifications()
            ->unread()
            ->count();

        return view('system.notifications', compact('notifications', 'unreadCount'));
    }

    public function markRead(int $id)
    {
        $notification = $this->visibleNotifications()->findOrFail($id);
        $notification->update(['read_at' => now()]);

        return redirect()->back();
    }

    public function markAllRead()
    {
        $this->visibleNotifications()
            ->unread()
            ->update(['read_at' => now()]);

        return redirect()->back()->with('status', 'All caught up.');
    }

    public function destroy(int $id)
    {
        $notification = $this->visibleNotifications()->findOrFail($id);
        $notification->delete();

        return redirect()->back();
    }

    private function visibleNotifications()
    {
        return AppNotification::query()->where(function ($query) {
            $query->where('user_id', auth()->id())
                ->orWhereNull('user_id');
        });
    }
}
