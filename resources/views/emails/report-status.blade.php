<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Report case update</title></head>
<body style="margin:0;background:#f3f5fa;font-family:Arial,sans-serif;color:#172033;padding:28px 12px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center">
    <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #dfe4ee;border-radius:14px;overflow:hidden;">
        <tr><td style="padding:24px 28px;background:linear-gradient(135deg,#5b7fff,#8b5cf6);color:#fff;"><b style="font-size:20px;">AI Hub Trust & Safety</b></td></tr>
        <tr><td style="padding:28px;">
            <h1 style="font-size:22px;margin:0 0 12px;">Case {{ ucfirst($communityReport->status) }}</h1>
            <p style="line-height:1.7;color:#526078;">Your community report <b>#{{ $communityReport->id }}</b> has completed moderation review.</p>
            @if($communityReport->resolution_note)
                <div style="background:#f7f8fc;border-left:4px solid #6d7cff;border-radius:7px;padding:14px 16px;margin:20px 0;line-height:1.65;color:#46536a;"><b>Resolution</b><br>{{ $communityReport->resolution_note }}</div>
            @endif
            <p style="font-size:12px;color:#8792a6;margin-top:24px;">Thank you for helping keep the AI Hub community accurate and safe.</p>
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
