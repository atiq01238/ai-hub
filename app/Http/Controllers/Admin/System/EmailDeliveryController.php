<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\EmailDeliveryLog;
use Illuminate\Http\Request;

class EmailDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailDeliveryLog::with('user')->latest();
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($search = trim((string)$request->query('search'))) {
            $query->where(fn($q)=>$q->where('subject','like',"%{$search}%")
                ->orWhereHas('user',fn($u)=>$u->where('email','like',"%{$search}%")));
        }
        $logs = $query->paginate(30)->withQueryString();
        $stats = [
            'queued'=>EmailDeliveryLog::where('status','queued')->count(),
            'sent'=>EmailDeliveryLog::where('status','sent')->count(),
            'failed'=>EmailDeliveryLog::where('status','failed')->count(),
            'total'=>EmailDeliveryLog::count(),
        ];
        $categories = EmailDeliveryLog::query()->distinct()->orderBy('category')->pluck('category');
        return view('system.email-deliveries', compact('logs','stats','categories'));
    }
}
