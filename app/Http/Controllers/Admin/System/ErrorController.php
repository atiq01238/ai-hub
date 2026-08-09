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

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $errors = $query->latest('last_seen_at')->paginate(20)->withQueryString();

        $stats = [
            'total'     => AppError::count(),
            'critical'  => AppError::get()->filter(fn ($e) => $e->severity === 'critical')->count(),
            'open'      => AppError::where('status', 'open')->count(),
            'resolved'  => AppError::where('status', 'resolved')->count(),
        ];

        return view('system.errors', compact('errors', 'stats'));
    }

    public function show(int $id)
    {
        $error = AppError::with('user')->findOrFail($id);

        return view('system.error-detail', compact('error'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status'           => ['required', 'in:open,investigating,resolved'],
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        AppError::findOrFail($id)->update($data);

        return redirect()->back()->with('status', 'Error updated.');
    }
}
