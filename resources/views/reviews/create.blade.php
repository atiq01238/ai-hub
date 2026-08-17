<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Review {{ $tool->name }} · AI Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root{color-scheme:dark;--bg:#080b12;--surface:#111724;--surface2:#171f2f;--border:#283247;--text:#f5f7ff;--muted:#94a3b8;--brand:#6d7cff;--brand2:#8b5cf6;--danger:#f87171;--success:#34d399;--star:#fbbf24}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,sans-serif;color:var(--text);background:radial-gradient(circle at 10% 0,rgba(109,124,255,.2),transparent 35%),var(--bg);padding:38px 18px}.shell{width:min(680px,100%);margin:auto}.brand{display:flex;align-items:center;gap:10px;color:inherit;text-decoration:none;margin-bottom:26px}.mark{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--brand),var(--brand2));font-weight:800}.brand b,.card h1{font-family:'Space Grotesk'}.card{border:1px solid var(--border);border-radius:18px;background:rgba(17,23,36,.94);box-shadow:0 25px 80px rgba(0,0,0,.32);padding:32px}.tool{display:flex;align-items:center;gap:14px;padding-bottom:24px;border-bottom:1px solid var(--border)}.avatar{display:grid;place-items:center;width:52px;height:52px;border-radius:14px;background:var(--surface2);border:1px solid var(--border);font-weight:800}.tool h1{font-size:26px;margin:0 0 4px}.muted{color:var(--muted);font-size:13px;line-height:1.6}.field{margin-top:22px}.field label{display:block;font-size:13px;font-weight:700;margin-bottom:8px}.rating{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:5px}.rating input{position:absolute;opacity:0}.rating label{font-size:38px;line-height:1;color:#3b465b;cursor:pointer;transition:.15s}.rating label:hover,.rating label:hover~label,.rating input:checked~label{color:var(--star);transform:translateY(-1px)}textarea{width:100%;min-height:150px;resize:vertical;border:1px solid var(--border);border-radius:11px;background:var(--surface2);color:var(--text);padding:13px;font:inherit;outline:none}textarea:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(109,124,255,.12)}.actions{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-top:22px}.button{border:0;border-radius:10px;background:linear-gradient(135deg,var(--brand),var(--brand2));color:white;padding:12px 20px;font-weight:750;cursor:pointer}.back{color:var(--muted);text-decoration:none;font-size:13px}.alert{padding:13px 15px;border-radius:10px;margin:18px 0 0;font-size:13px}.success{background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.26);color:#6ee7b7}.error{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:#fca5a5}.status{display:inline-flex;margin-top:7px;padding:3px 8px;border-radius:99px;background:rgba(109,124,255,.12);color:#a5b4fc;font-size:11px;font-weight:700}@media(max-width:560px){body{padding:20px 12px}.card{padding:24px 20px}.actions{flex-direction:column-reverse;align-items:stretch}.button{width:100%}.rating label{font-size:34px}}
    </style>
</head>
<body>
<main class="shell">
    <a href="{{ route('home') }}" class="brand"><span class="mark">AI</span><span><b>AI Hub</b><br><span class="muted">Community Reviews</span></span></a>
    <section class="card">
        <div class="tool">
            <div class="avatar">{{ mb_strtoupper(mb_substr($tool->name, 0, 2)) }}</div>
            <div>
                <h1>{{ $existingReview ? 'Update your review' : 'Review ' . $tool->name }}</h1>
                <div class="muted">Honest ratings help the community choose the right AI tools.</div>
                @if($existingReview)<span class="status">Current status: {{ ucfirst($existingReview->status) }}</span>@endif
            </div>
        </div>

        @if(session('status'))<div class="alert success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif

        <form action="{{ route('reviews.store', $tool->id) }}" method="POST">
            @csrf
            <div class="field">
                <label>Your rating</label>
                <div class="rating" aria-label="Rating from 1 to 5 stars">
                    @for($star = 5; $star >= 1; $star--)
                        <input type="radio" id="star{{ $star }}" name="rating" value="{{ $star }}" required @checked((float) old('rating', $existingReview?->rating) === (float) $star)>
                        <label for="star{{ $star }}" title="{{ $star }} stars">★</label>
                    @endfor
                </div>
            </div>
            <div class="field">
                <label for="body">Your experience <span class="muted">(optional)</span></label>
                <textarea id="body" name="body" maxlength="2000" placeholder="What worked well? What could be better? Keep it specific and respectful.">{{ old('body', $existingReview?->body) }}</textarea>
                <div class="muted" style="margin-top:7px;">Reviews are moderated before publication. Updating a published review returns it to the queue.</div>
            </div>
            <div class="actions">
                <a href="{{ route('home') }}" class="back">← Return to AI Hub</a>
                <button type="submit" class="button">{{ $existingReview ? 'Update review' : 'Submit review' }}</button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
