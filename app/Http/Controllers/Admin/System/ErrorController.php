<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\AppError;
use Illuminate\Http\Request;

class ErrorController extends Controller
{
    public function index(Request $request)
    {
        $query = AppError::query();
        if ($status = $request->query('status')) $query->where('status', $status);
        if ($severity = $request->query('severity')) {
            $ids = AppError::get()->filter(fn ($e) => $e->severity === $severity)->pluck('id');
            $query->whereIn('id', $ids);
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('exception_class', 'like', "%{$search}%")->orWhere('message', 'like', "%{$search}%")->orWhere('url', 'like', "%{$search}%"));
        }

        $errors = $query->latest('last_seen_at')->paginate(20)->withQueryString();
        $all = AppError::get();
        $stats = [
            'total' => $all->count(),
            'critical' => $all->filter(fn ($e) => $e->severity === 'critical')->count(),
            'open' => $all->where('status', 'open')->count(),
            'investigating' => $all->where('status', 'investigating')->count(),
            'resolved' => $all->where('status', 'resolved')->count(),
            'occurrences_24h' => $all->where('last_seen_at', '>=', now()->subDay())->sum('occurrence_count'),
        ];
        $trend = collect(range(6, 0))->map(fn ($d) => [
            'label' => now()->subDays($d)->format('D'),
            'count' => AppError::whereDate('last_seen_at', now()->subDays($d)->toDateString())->sum('occurrence_count'),
        ]);

        return view('system.errors', compact('errors', 'stats', 'trend'));
    }

    public function show(int $id)
    {
        $error = AppError::with('user')->findOrFail($id);
        return view('system.error-detail', compact('error'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', 'in:open,investigating,resolved'], 'resolution_notes' => ['nullable', 'string', 'max:2000']]);
        AppError::findOrFail($id)->update($data);
        return redirect()->back()->with('status', 'Error updated.');
    }
}
