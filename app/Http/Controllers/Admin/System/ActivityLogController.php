<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        if ($subject = $request->query('subject_type')) {
            $query->where('subject_type', 'App\\Models\\' . $subject);
        }

        $logs = $query->latest()->paginate(30)->withQueryString();

        $users = User::orderBy('name')->get();
        $subjectTypes = ActivityLog::select('subject_type')->distinct()->pluck('subject_type');

        return view('system.activity-logs', compact('logs', 'users', 'subjectTypes'));
    }
}
