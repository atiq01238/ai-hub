<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('reviews')->latest();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $users = $query->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function show(int $id)
    {
        $user = User::withCount('reviews')->findOrFail($id);
        $recentReviews = $user->reviews()->with('tool')->latest()->take(5)->get();

        return view('users.show', compact('user', 'recentReviews'));
    }

    public function suspend(int $id)
    {
        $user = User::findOrFail($id);
        $user->status = 'suspended';
        $user->save();

        return redirect()->back()->with('status', 'User suspended.');
    }

    public function activate(int $id)
    {
        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();

        return redirect()->back()->with('status', 'User activated.');
    }

    public function reports()
    {
        // Placeholder — a real "reports" feed needs a user_reports table
        // (visitors flagging reviews/content), which doesn't exist yet.
        return view('users.reports');
    }
}
