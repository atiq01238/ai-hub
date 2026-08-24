<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>AI Orbit response</title></head>
<body style="margin:0;background:#f3f5fa;font-family:Arial,sans-serif;color:#172033;padding:28px 12px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td align="center">
<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="width:100%;max-width:620px;background:#fff;border:1px solid #dfe4ee;border-radius:14px;overflow:hidden;">
<tr><td style="padding:18px 28px;background:#050817;color:#fff;">@php($emailBrandLogo = isset($message) && is_file(public_path(config('brand.assets.wordmark'))) ? $message->embed(public_path(config('brand.assets.wordmark'))) : asset(config('brand.assets.wordmark')))
<img src="{{ $emailBrandLogo }}" alt="AI Orbit" width="220" style="display:block;width:220px;max-width:100%;height:auto;border:0;outline:none;"></td></tr>
<tr><td style="padding:28px;">
<h1 style="font-size:22px;margin:0 0 12px;">Hello {{ $contactMessage->name }},</h1>
<p style="line-height:1.7;color:#526078;">Thank you for contacting AI Orbit about <b>{{ $contactMessage->subject }}</b>. An administrator has replied:</p>
<div style="background:#f7f8fc;border-left:4px solid #6d7cff;border-radius:7px;padding:16px;margin:20px 0;line-height:1.7;color:#46536a;white-space:pre-line;">{{ $contactReply->body }}</div>
<p style="line-height:1.7;color:#526078;">If you need to add more information, you can contact us again and reference message <b>#{{ $contactMessage->id }}</b>.</p>
<p style="font-size:12px;color:#8792a6;margin-top:24px;">AI Orbit · Contact reference #{{ $contactMessage->id }}</p>
</td></tr></table>
</td></tr></table>
</body></html>
