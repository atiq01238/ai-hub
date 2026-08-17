<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\NotificationRule;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
    public function create()
    {
        return view('submissions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'submission_type'   => ['required', 'in:tool,model,company,correction'],
            'tool_name'          => ['required', 'string', 'max:255'],
            'submitted_by_email' => ['required', 'email', 'max:255'],
            'website'            => ['nullable', 'url', 'max:255'],
            'category'           => ['nullable', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:2000'],
            'company_name'       => ['nullable', 'max:0'],
        ]);

        unset($data['company_name']);
        $data['submitted_by_email'] = $request->user()?->email ?? $data['submitted_by_email'];
        $data['user_id'] = auth()->id();

        $duplicate = Submission::where('submitted_by_email', $data['submitted_by_email'])
            ->where('submission_type', $data['submission_type'])
            ->where('tool_name', $data['tool_name'])
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'tool_name' => 'A matching submission from this email is already awaiting review.',
            ]);
        }

        $submission = Submission::create($data);

        if (NotificationRule::isEnabled('new_submission')) {
            AppNotification::broadcast(
                'lightbulb',
                'info',
                'New community submission',
                ucfirst($submission->submission_type) . ": \"{$submission->tool_name}\" awaiting review"
            );
        }

        return redirect()->route('submissions.create')
            ->with('status', "Thanks! Submission #{$submission->id} is now in the moderation queue.");
    }
}