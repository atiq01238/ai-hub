<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\NotificationRule;
use App\Models\Report;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'reportable_type' => ['required', 'in:user,review,submission'],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'in:spam,harassment,impersonation,fraud,misinformation,privacy,other'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $types = [
            'user' => User::class,
            'review' => Review::class,
            'submission' => Submission::class,
        ];

        $modelClass = $types[$data['reportable_type']];
        $subject = $modelClass::findOrFail($data['reportable_id']);

        if ($subject instanceof User && $subject->is($request->user())) {
            throw ValidationException::withMessages([
                'reportable_id' => 'You cannot report your own account.',
            ]);
        }

        $duplicate = Report::open()
            ->where('reporter_id', $request->user()->id)
            ->whereMorphedTo('reportable', $subject)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'reportable_id' => 'You already have an open report for this item.',
            ]);
        }

        $priority = in_array($data['reason'], ['fraud', 'impersonation', 'privacy'], true)
            ? 'high'
            : 'medium';

        $report = $subject->reports()->create([
            'reporter_id' => $request->user()->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'priority' => $priority,
        ]);

        if (NotificationRule::isEnabled('new_report')) {
            AppNotification::broadcast(
                'flag',
                $priority === 'high' ? 'warn' : 'info',
                'New community report',
                ucfirst($data['reason']) . " report #{$report->id} awaiting moderation"
            );
        }

        return back()->with('status', 'Report submitted. Our moderation team will review it.');
    }
}