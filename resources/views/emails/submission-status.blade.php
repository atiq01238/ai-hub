<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Submission update</title></head>
<body style="margin:0;background:#f3f5fa;font-family:Arial,sans-serif;color:#172033;padding:28px 12px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center">
    <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #dfe4ee;border-radius:14px;overflow:hidden;">
        <tr><td style="padding:24px 28px;background:linear-gradient(135deg,#5b7fff,#8b5cf6);color:#fff;"><b style="font-size:20px;">AI Hub</b><div style="font-size:12px;opacity:.85;margin-top:3px;">Community contribution update</div></td></tr>
        <tr><td style="padding:28px;">
            <h1 style="font-size:22px;margin:0 0 12px;">Submission {{ ucfirst(str_replace('_', ' ', $submission->status)) }}</h1>
            <p style="line-height:1.7;color:#526078;">Your {{ $submission->submission_type }} contribution <b>“{{ $submission->tool_name }}”</b> has been reviewed by the AI Hub moderation team.</p>
            @if($submission->admin_notes)
                <div style="background:#f7f8fc;border-left:4px solid #6d7cff;border-radius:7px;padding:14px 16px;margin:20px 0;line-height:1.65;color:#46536a;"><b>Moderator note</b><br>{{ $submission->admin_notes }}</div>
            @endif
            @if($submission->status === 'needs_info')
                <p style="line-height:1.7;color:#526078;">Please send the missing information through a new contribution and reference submission <b>#{{ $submission->id }}</b>.</p>
                <p><a href="{{ route('submissions.create') }}" style="display:inline-block;background:#5b7fff;color:#fff;text-decoration:none;font-weight:bold;border-radius:8px;padding:11px 16px;">Send additional information</a></p>
            @elseif($submission->status === 'approved')
                <p style="line-height:1.7;color:#526078;">The contribution has entered our verification and publishing workflow. Approval does not automatically mean immediate public publication.</p>
            @endif
            <p style="font-size:12px;color:#8792a6;margin-top:24px;">Submission #{{ $submission->id }} · Submitted {{ $submission->created_at->format('M j, Y') }}</p>
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
