<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Submission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function create()
    {
        return view('submissions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tool_name'          => ['required', 'string', 'max:255'],
            'submitted_by_email' => ['required', 'email', 'max:255'],
            'website'            => ['nullable', 'url', 'max:255'],
            'category'           => ['nullable', 'string', 'max:100'],
            'description'        => ['nullable', 'string', 'max:2000'],
        ]);

        $data['user_id'] = auth()->id();

        $submission = Submission::create($data);

        AppNotification::broadcast(
            'lightbulb',
            'info',
            'New tool submission',
            "\"{$submission->tool_name}\" awaiting review"
        );

        return redirect()
            ->back()
            ->with('status', "Thanks! We'll review your suggestion soon.");
    }
}
