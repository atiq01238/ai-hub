<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contribute to AI Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root{color-scheme:dark;--bg:#080b12;--surface:#111724;--surface2:#171f2f;--border:#283247;--text:#f5f7ff;--muted:#94a3b8;--brand:#6d7cff;--brand2:#8b5cf6;--danger:#f87171;--success:#34d399}
        *{box-sizing:border-box} body{margin:0;min-height:100vh;font-family:Inter,sans-serif;color:var(--text);background:radial-gradient(circle at 15% 0,rgba(109,124,255,.18),transparent 34%),radial-gradient(circle at 90% 10%,rgba(139,92,246,.14),transparent 30%),var(--bg);padding:40px 18px}.shell{width:min(820px,100%);margin:auto}.brand{display:flex;align-items:center;gap:11px;color:var(--text);text-decoration:none;margin-bottom:28px}.mark{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--brand),var(--brand2));font-weight:800}.brand b{font-family:'Space Grotesk';font-size:18px}.card{border:1px solid var(--border);border-radius:18px;background:rgba(17,23,36,.93);box-shadow:0 24px 80px rgba(0,0,0,.3);overflow:hidden}.hero{padding:32px 34px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,rgba(109,124,255,.11),rgba(139,92,246,.05))}.eyebrow{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#a5b4fc;font-weight:700}.hero h1{font-family:'Space Grotesk';font-size:clamp(28px,5vw,42px);line-height:1.08;margin:10px 0}.hero p{color:var(--muted);line-height:1.7;max-width:650px;margin:0}.form{padding:30px 34px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.field{display:flex;flex-direction:column;gap:7px}.field.full{grid-column:1/-1}.field label{font-size:13px;font-weight:650}.hint{font-size:11.5px;color:var(--muted)}input,select,textarea{width:100%;border:1px solid var(--border);border-radius:10px;background:var(--surface2);color:var(--text);padding:12px 13px;font:inherit;outline:none}input:focus,select:focus,textarea:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(109,124,255,.12)}textarea{resize:vertical;min-height:130px}.actions{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-top:24px}.button{border:0;border-radius:10px;background:linear-gradient(135deg,var(--brand),var(--brand2));color:white;padding:12px 20px;font-weight:700;cursor:pointer}.back{color:var(--muted);text-decoration:none;font-size:13px}.alert{padding:13px 15px;border-radius:10px;margin-bottom:18px;font-size:13px;line-height:1.5}.success{background:rgba(52,211,153,.11);border:1px solid rgba(52,211,153,.28);color:#6ee7b7}.error{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:#fca5a5}.hp{position:absolute!important;left:-9999px!important}@media(max-width:650px){body{padding:20px 12px}.hero,.form{padding:24px 20px}.grid{grid-template-columns:1fr}.field.full{grid-column:auto}.actions{align-items:stretch;flex-direction:column-reverse}.button{width:100%}}
    </style>
</head>
<body>
<main class="shell">
    <a href="{{ route('home') }}" class="brand"><span class="mark">AI</span><span><b>AI Hub</b><br><span class="hint">Research & Intelligence Platform</span></span></a>

    <section class="card">
        <div class="hero">
            <div class="eyebrow">Community Contribution</div>
            <h1>Help improve the AI directory.</h1>
            <p>Suggest a tool, model or company—or send a correction. Every contribution enters a moderated review queue before anything is published.</p>
        </div>
        <div class="form">
            @if (session('status'))<div class="alert success">{{ session('status') }}</div>@endif
            @if ($errors->any())
                <div class="alert error"><b>Please fix the following:</b><ul style="margin:7px 0 0;padding-left:18px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('submissions.store') }}" method="POST">
                @csrf
                <div class="hp" aria-hidden="true"><label>Company name<input name="company_name" tabindex="-1" autocomplete="off"></label></div>
                <div class="grid">
                    <div class="field">
                        <label for="submission_type">Contribution type</label>
                        <select id="submission_type" name="submission_type" required>
                            <option value="tool" @selected(old('submission_type', 'tool') === 'tool')>AI Tool</option>
                            <option value="model" @selected(old('submission_type') === 'model')>AI Model</option>
                            <option value="company" @selected(old('submission_type') === 'company')>AI Company</option>
                            <option value="correction" @selected(old('submission_type') === 'correction')>Data Correction</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="tool_name">Name or correction subject</label>
                        <input id="tool_name" name="tool_name" required maxlength="255" value="{{ old('tool_name') }}" placeholder="e.g. Example AI">
                    </div>
                    <div class="field">
                        <label for="submitted_by_email">Contact email</label>
                        <input type="email" id="submitted_by_email" name="submitted_by_email" required maxlength="255" value="{{ auth()->user()?->email ?? old('submitted_by_email') }}" @readonly(auth()->check())>
                        <span class="hint">Used only if moderators need clarification.</span>
                    </div>
                    <div class="field">
                        <label for="website">Official website <span class="hint">(optional)</span></label>
                        <input type="url" id="website" name="website" maxlength="255" placeholder="https://" value="{{ old('website') }}">
                    </div>
                    <div class="field full">
                        <label for="category">Suggested category <span class="hint">(optional)</span></label>
                        <input id="category" name="category" maxlength="100" value="{{ old('category') }}" placeholder="e.g. Image Generation">
                    </div>
                    <div class="field full">
                        <label for="description">Contribution details</label>
                        <textarea id="description" name="description" maxlength="2000" placeholder="Explain what should be added or corrected, and include enough detail for verification...">{{ old('description') }}</textarea>
                        <span class="hint">Maximum 2,000 characters. Please do not include passwords or private information.</span>
                    </div>
                </div>
                <div class="actions">
                    <a class="back" href="{{ route('home') }}">← Return to AI Hub</a>
                    <button class="button" type="submit">Send for review</button>
                </div>
            </form>
        </div>
    </section>
</main>
</body>
</html>
