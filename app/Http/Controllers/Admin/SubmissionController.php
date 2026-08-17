<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SubmissionStatusMail;
use App\Models\Category;
use App\Models\Submission;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Submission::query()->with(['user', 'reviewer', 'convertedTool']);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('tool_name', 'like', "%{$search}%")
                    ->orWhere('submitted_by_email', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['pending', 'approved', 'rejected', 'needs_info'], true)) {
                $query->where('status', $status);
            }
        }

        if ($type = $request->query('type')) {
            if (in_array($type, ['tool', 'model', 'company', 'correction'], true)) {
                $query->where('submission_type', $type);
            }
        }

        return view('submissions.index', [
            'submissions' => $query->latest()->paginate(20)->withQueryString(),
            'counts' => [
                'all' => Submission::count(),
                'pending' => Submission::where('status', 'pending')->count(),
                'needs_info' => Submission::where('status', 'needs_info')->count(),
                'approved' => Submission::where('status', 'approved')->count(),
                'rejected' => Submission::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $submission = Submission::with(['user', 'reviewer', 'convertedTool'])->findOrFail($id);

        return view('submissions.show', compact('submission'));
    }

    public function approve(Request $request, int $id)
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:1000']]);
        $submission = Submission::findOrFail($id);

        abort_unless(in_array($submission->status, ['pending', 'needs_info'], true), 422, 'This submission has already been reviewed.');

        DB::transaction(function () use ($submission, $request, $data) {
            $tool = $submission->submission_type === 'tool'
                ? $this->findOrCreateToolDraft($submission)
                : null;

            $submission->update([
                'status' => 'approved',
                'admin_notes' => $data['admin_notes'] ?? null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'converted_tool_id' => $tool?->id,
            ]);
        });

        $message = $submission->submission_type === 'tool'
            ? 'Submission approved and linked to an AI Tool draft.'
            : 'Submission approved.';

        $this->notifySubmitter($submission->fresh());

        return back()->with('status', $message);
    }

    public function reject(Request $request, int $id)
    {
        $data = $request->validate(['admin_notes' => ['required', 'string', 'max:1000']]);

        $submission = Submission::findOrFail($id);
        abort_unless(in_array($submission->status, ['pending', 'needs_info'], true), 422, 'This submission has already been reviewed.');

        $submission->update([
            'status'      => 'rejected',
            'admin_notes' => $data['admin_notes'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->notifySubmitter($submission->fresh());

        return back()->with('status', 'Submission rejected with a moderation note.');
    }

    public function requestInfo(Request $request, int $id)
    {
        $data = $request->validate(['admin_notes' => ['required', 'string', 'max:1000']]);

        $submission = Submission::findOrFail($id);
        abort_unless(in_array($submission->status, ['pending', 'needs_info'], true), 422, 'This submission has already been reviewed.');

        $submission->update([
            'status'      => 'needs_info',
            'admin_notes' => $data['admin_notes'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->notifySubmitter($submission->fresh());

        return back()->with('status', 'More information requested and recorded.');
    }

    private function findOrCreateToolDraft(Submission $submission): Tool
    {
        if ($submission->converted_tool_id) {
            return Tool::findOrFail($submission->converted_tool_id);
        }

        $existing = Tool::query()
            ->when(
                $submission->website,
                fn ($q) => $q->where('website', $submission->website),
                fn ($q) => $q->whereRaw('LOWER(name) = ?', [Str::lower($submission->tool_name)])
            )
            ->first();

        if ($existing) {
            return $existing;
        }

        $baseSlug = Str::slug($submission->tool_name) ?: 'community-tool';
        $slug = $baseSlug;
        $suffix = 2;

        while (Tool::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        $categoryId = $submission->category
            ? Category::whereRaw('LOWER(name) = ?', [Str::lower($submission->category)])->value('id')
            : null;

        return Tool::create([
            'name' => $submission->tool_name,
            'slug' => $slug,
            'website' => $submission->website,
            'category_id' => $categoryId,
            'short_description' => Str::limit((string) $submission->description, 240, ''),
            'description' => $submission->description,
            'status' => 'draft',
        ]);
    }

    private function notifySubmitter(Submission $submission): void
    {
        try {
            Mail::to($submission->submitted_by_email)->queue(new SubmissionStatusMail($submission));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}