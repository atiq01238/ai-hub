<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ReportStatusMail;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::query()->with(['reporter', 'reportable', 'assignee']);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('reporter', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['pending', 'reviewing', 'resolved', 'dismissed'], true)) {
                $query->where('status', $status);
            }
        }

        if ($priority = $request->query('priority')) {
            if (in_array($priority, ['low', 'medium', 'high', 'critical'], true)) {
                $query->where('priority', $priority);
            }
        }

        if ($type = $request->query('type')) {
            $types = [
                'user' => \App\Models\User::class,
                'review' => \App\Models\Review::class,
                'submission' => \App\Models\Submission::class,
            ];

            if (isset($types[$type])) {
                $query->where('reportable_type', $types[$type]);
            }
        }

        return view('community.reports.index', [
            'reports' => $query->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
                ->latest()->paginate(20)->withQueryString(),
            'counts' => [
                'all' => Report::count(),
                'pending' => Report::where('status', 'pending')->count(),
                'reviewing' => Report::where('status', 'reviewing')->count(),
                'resolved' => Report::where('status', 'resolved')->count(),
                'critical' => Report::open()->where('priority', 'critical')->count(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $report = Report::with(['reporter', 'reportable', 'assignee', 'resolver'])->findOrFail($id);

        return view('community.reports.show', compact('report'));
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['required', 'in:reviewing,resolved,dismissed'],
            'priority' => ['required', 'in:low,medium,high,critical'],
            'resolution_note' => [
                in_array($request->input('status'), ['resolved', 'dismissed'], true) ? 'required' : 'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $report = Report::findOrFail($id);
        $closing = in_array($data['status'], ['resolved', 'dismissed'], true);

        $report->update([
            'status' => $data['status'],
            'priority' => $data['priority'],
            'assigned_to' => $report->assigned_to ?: $request->user()->id,
            'resolved_by' => $closing ? $request->user()->id : null,
            'resolution_note' => $data['resolution_note'] ?? null,
            'resolved_at' => $closing ? now() : null,
        ]);

        if ($closing && $report->reporter?->email) {
            try {
                Mail::to($report->reporter->email)->queue(new ReportStatusMail($report->fresh()));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('status', 'Report workflow updated successfully.');
    }
}