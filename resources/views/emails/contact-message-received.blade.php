<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>New contact message</title></head>
<body style="margin:0;background:#f3f5fa;font-family:Arial,sans-serif;color:#172033;padding:28px 12px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center">
<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="width:100%;max-width:620px;background:#fff;border:1px solid #dfe4ee;border-radius:14px;overflow:hidden;">
<tr><td style="padding:18px 28px;background:#050817;color:#fff;">@php($emailBrandLogo = isset($message) && is_file(public_path(config('brand.assets.wordmark'))) ? $message->embed(public_path(config('brand.assets.wordmark'))) : asset(config('brand.assets.wordmark')))
<img src="{{ $emailBrandLogo }}" alt="AI Orbit" width="220" style="display:block;width:220px;max-width:100%;height:auto;border:0;outline:none;"></td></tr>
<tr><td style="padding:28px;">
<h1 style="font-size:22px;margin:0 0 8px;">New contact message</h1>
<p style="margin:0 0 20px;color:#65738b;">A user submitted a message through the AI Orbit Contact Us form.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="7" style="font-size:14px;"><tr><td style="color:#7a879b;width:110px;">From</td><td><b>{{ $contactMessage->name }}</b> · {{ $contactMessage->email }}</td></tr><tr><td style="color:#7a879b;">Topic</td><td>{{ $contactMessage->topic_label }}</td></tr><tr><td style="color:#7a879b;">Subject</td><td>{{ $contactMessage->subject }}</td></tr></table>
<div style="background:#f7f8fc;border-left:4px solid #6d7cff;border-radius:7px;padding:16px;margin:20px 0;line-height:1.65;color:#46536a;white-space:pre-line;">{{ $contactMessage->message }}</div>
<a href="{{ route('admin.contact-messages.show',$contactMessage) }}" style="display:inline-block;padding:11px 17px;border-radius:8px;background:#637bff;color:#fff;text-decoration:none;font-weight:700;">Open in Admin Inbox</a>
<p style="font-size:12px;color:#8792a6;margin-top:24px;">Message #{{ $contactMessage->id }} · {{ $contactMessage->created_at->format('M j, Y g:i A') }}</p>
</td></tr></table>
</td></tr></table>
</body></html>
