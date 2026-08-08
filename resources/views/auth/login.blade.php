<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Hub Intelligence — Admin Sign In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --bg-0: #050611;
      --bg-1: #0a0c1c;
      --bg-2: #0f1228;
      --glass: rgba(255, 255, 255, 0.045);
      --glass-border: rgba(255, 255, 255, 0.09);
      --violet: #7c5cff;
      --indigo: #4f6dff;
      --cyan: #22d3ee;
      --text-0: #f4f5fb;
      --text-1: #a8adc4;
      --text-2: #6a708c;
      --danger: #ff5c7a;
      --success: #37e6b0;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html,
    body {
      height: 100%;
    }

    body {
      font-family: 'Inter', sans-serif;
      background:
        radial-gradient(ellipse 900px 600px at 15% 10%, rgba(124, 92, 255, 0.16), transparent 60%),
        radial-gradient(ellipse 700px 500px at 85% 85%, rgba(34, 211, 238, 0.10), transparent 60%),
        linear-gradient(180deg, var(--bg-0) 0%, var(--bg-1) 50%, var(--bg-0) 100%);
      color: var(--text-0);
      min-height: 100vh;
      overflow-x: hidden;
      position: relative;
    }

    ::selection {
      background: rgba(124, 92, 255, 0.35);
    }

    /* ---------- Particle canvas ---------- */
    #particles {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
    }

    /* ---------- Layout ---------- */
    .shell {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: 1.15fr 1fr;
      min-height: 100vh;
    }

    @media (max-width: 980px) {
      .shell {
        grid-template-columns: 1fr;
      }

      .hero {
        display: none;
      }
    }

    /* ---------- Hero (left) ---------- */
    .hero {
      position: relative;
      padding: 56px 64px 48px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-right: 1px solid rgba(255, 255, 255, 0.06);
      overflow: hidden;
    }

    .hero::before {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 220px;
      background: linear-gradient(180deg, transparent, rgba(124, 92, 255, 0.08));
      filter: blur(20px);
      pointer-events: none;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 2;
    }

    .brand-mark {
      width: 38px;
      height: 38px;
      border-radius: 11px;
      background: linear-gradient(135deg, var(--violet), var(--cyan));
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 24px rgba(124, 92, 255, 0.55), inset 0 0 12px rgba(255, 255, 255, 0.25);
      flex-shrink: 0;
    }

    .brand-mark svg {
      width: 20px;
      height: 20px;
    }

    .brand-text {
      display: flex;
      flex-direction: column;
      line-height: 1.15;
    }

    .brand-text .name {
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 600;
      font-size: 16.5px;
      letter-spacing: 0.01em;
    }

    .brand-text .tag {
      font-family: 'JetBrains Mono', monospace;
      font-size: 10.5px;
      letter-spacing: 0.14em;
      color: var(--text-2);
      text-transform: uppercase;
    }

    .hero-copy {
      z-index: 2;
      margin-top: 12px;
    }

    .eyebrow {
      font-family: 'JetBrains Mono', monospace;
      font-size: 11px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--cyan);
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 18px;
    }

    .eyebrow .dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--success);
      box-shadow: 0 0 8px var(--success);
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 1;
      }

      50% {
        opacity: 0.35;
      }
    }

    .hero h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 600;
      font-size: clamp(30px, 3vw, 42px);
      line-height: 1.12;
      letter-spacing: -0.01em;
      max-width: 520px;
    }

    .hero h1 span {
      background: linear-gradient(120deg, var(--violet), var(--cyan) 70%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .hero p {
      margin-top: 16px;
      max-width: 440px;
      color: var(--text-1);
      font-size: 14.5px;
      line-height: 1.6;
    }

    /* ---------- Hologram stage ---------- */
    .stage {
      position: relative;
      height: 280px;
      margin: 36px 0 8px;
      z-index: 2;
    }

    .stage svg {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
    }

    .core-glow {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 180px;
      height: 180px;
      transform: translate(-50%, -50%);
      background: radial-gradient(circle, rgba(124, 92, 255, 0.45), rgba(34, 211, 238, 0.12) 55%, transparent 72%);
      filter: blur(6px);
      border-radius: 50%;
      animation: breathe 4s ease-in-out infinite;
    }

    @keyframes breathe {

      0%,
      100% {
        transform: translate(-50%, -50%) scale(1);
      }

      50% {
        transform: translate(-50%, -50%) scale(1.08);
      }
    }

    .float-card {
      position: absolute;
      background: var(--glass);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(14px);
      border-radius: 12px;
      padding: 9px 12px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 10.5px;
      color: var(--text-1);
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
      animation: drift 6s ease-in-out infinite;
    }

    .float-card b {
      color: var(--text-0);
      font-size: 12.5px;
      display: block;
      font-family: 'Space Grotesk', sans-serif;
    }

    .fc1 {
      top: 6%;
      left: 2%;
      animation-delay: 0s;
    }

    .fc2 {
      top: 12%;
      right: 0%;
      animation-delay: 1.2s;
    }

    .fc3 {
      bottom: 8%;
      left: 8%;
      animation-delay: 2.1s;
    }

    .fc4 {
      bottom: 2%;
      right: 10%;
      animation-delay: 0.6s;
    }

    @keyframes drift {

      0%,
      100% {
        transform: translateY(0px);
      }

      50% {
        transform: translateY(-9px);
      }
    }

    .floor-reflect {
      position: absolute;
      bottom: -16px;
      left: 10%;
      right: 10%;
      height: 14px;
      background: linear-gradient(90deg, transparent, rgba(124, 92, 255, 0.5), rgba(34, 211, 238, 0.4), transparent);
      filter: blur(10px);
      opacity: 0.6;
    }

    /* ---------- Feature list ---------- */
    .features {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px 22px;
      z-index: 2;
      margin-top: 8px;
    }

    .feature {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12.5px;
      color: var(--text-1);
      padding: 8px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.045);
    }

    .feature svg {
      width: 15px;
      height: 15px;
      color: var(--cyan);
      flex-shrink: 0;
    }

    .feature span {
      color: var(--text-0);
      font-weight: 500;
    }

    /* ---------- Right: login panel ---------- */
    .panel {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 32px;
      position: relative;
      z-index: 1;
    }

    .card {
      width: 100%;
      max-width: 400px;
      background: linear-gradient(160deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.02));
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 38px 34px 30px;
      backdrop-filter: blur(20px);
      position: relative;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
    }

    .card::before {
      content: "";
      position: absolute;
      inset: 0;
      border-radius: 20px;
      padding: 1px;
      background: linear-gradient(135deg, rgba(124, 92, 255, 0.5), rgba(34, 211, 238, 0.15), transparent 60%);
      -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      pointer-events: none;
      opacity: 0.7;
    }

    .card-mobile-brand {
      display: none;
      align-items: center;
      gap: 10px;
      margin-bottom: 26px;
    }

    @media (max-width:980px) {
      .card-mobile-brand {
        display: flex;
      }
    }

    .card-mobile-brand .brand-mark {
      width: 32px;
      height: 32px;
    }

    .card-mobile-brand .name {
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 600;
      font-size: 15px;
    }

    .card h2 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 23px;
      font-weight: 600;
      letter-spacing: -0.01em;
    }

    .card .sub {
      margin-top: 6px;
      font-size: 13px;
      color: var(--text-2);
    }

    form {
      margin-top: 26px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .field {
      position: relative;
    }

    .field label {
      display: block;
      font-size: 11.5px;
      font-weight: 500;
      color: var(--text-1);
      margin-bottom: 7px;
      letter-spacing: 0.01em;
    }

    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrap svg.icon {
      position: absolute;
      left: 13px;
      width: 16px;
      height: 16px;
      color: var(--text-2);
      pointer-events: none;
    }

    .input-wrap input {
      width: 100%;
      background: rgba(255, 255, 255, 0.035);
      border: 1px solid rgba(255, 255, 255, 0.09);
      border-radius: 11px;
      padding: 12px 14px 12px 40px;
      font-size: 13.5px;
      color: var(--text-0);
      font-family: 'Inter', sans-serif;
      outline: none;
      transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .input-wrap input::placeholder {
      color: var(--text-2);
    }

    .input-wrap input:focus {
      border-color: rgba(124, 92, 255, 0.6);
      box-shadow: 0 0 0 3px rgba(124, 92, 255, 0.14);
      background: rgba(255, 255, 255, 0.05);
    }

    .field.error input {
      border-color: var(--danger);
      box-shadow: 0 0 0 3px rgba(255, 92, 122, 0.12);
    }

    .field.success input {
      border-color: var(--success);
      box-shadow: 0 0 0 3px rgba(55, 230, 176, 0.12);
    }

    .hint {
      font-size: 11px;
      margin-top: 6px;
      display: none;
      align-items: center;
      gap: 5px;
    }

    .field.error .hint.error-hint {
      display: flex;
      color: var(--danger);
    }

    .field.success .hint.success-hint {
      display: flex;
      color: var(--success);
    }

    .hint svg {
      width: 12px;
      height: 12px;
    }

    .toggle-pass {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-2);
      display: flex;
      align-items: center;
      padding: 4px;
      transition: color .2s;
    }

    .toggle-pass:hover {
      color: var(--text-0);
    }

    .toggle-pass svg {
      width: 16px;
      height: 16px;
    }

    .row-between {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12.5px;
      margin-top: -2px;
    }

    .remember {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--text-1);
      cursor: pointer;
      user-select: none;
    }

    .remember input {
      appearance: none;
      width: 15px;
      height: 15px;
      border-radius: 5px;
      border: 1px solid rgba(255, 255, 255, 0.22);
      background: rgba(255, 255, 255, 0.03);
      cursor: pointer;
      position: relative;
      transition: all .15s;
    }

    .remember input:checked {
      background: linear-gradient(135deg, var(--violet), var(--cyan));
      border-color: transparent;
    }

    .remember input:checked::after {
      content: "";
      position: absolute;
      left: 5px;
      top: 1.5px;
      width: 4px;
      height: 8px;
      border: solid white;
      border-width: 0 1.6px 1.6px 0;
      transform: rotate(45deg);
    }

    .forgot {
      color: var(--cyan);
      text-decoration: none;
      font-weight: 500;
    }

    .forgot:hover {
      text-decoration: underline;
    }

    .btn-primary {
      margin-top: 6px;
      position: relative;
      width: 100%;
      padding: 13px;
      border: none;
      border-radius: 11px;
      background: linear-gradient(135deg, var(--violet), var(--indigo) 55%, var(--cyan));
      background-size: 160% 160%;
      color: #fff;
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 600;
      font-size: 14px;
      letter-spacing: 0.01em;
      cursor: pointer;
      box-shadow: 0 8px 26px rgba(124, 92, 255, 0.35);
      transition: background-position .4s ease, box-shadow .25s ease, transform .15s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-primary:hover {
      background-position: 100% 0;
      box-shadow: 0 10px 34px rgba(124, 92, 255, 0.5);
    }

    .btn-primary:active {
      transform: scale(0.98);
    }

    .btn-primary .spinner {
      width: 15px;
      height: 15px;
      border: 2px solid rgba(255, 255, 255, 0.35);
      border-top-color: #fff;
      border-radius: 50%;
      display: none;
      animation: spin .7s linear infinite;
    }

    .btn-primary.loading .btn-label {
      opacity: 0.75;
    }

    .btn-primary.loading .spinner {
      display: inline-block;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 22px 0 4px;
      color: var(--text-2);
      font-size: 11.5px;
    }

    .divider::before,
    .divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: rgba(255, 255, 255, 0.09);
    }

    .social-row {
      display: flex;
      gap: 10px;
      margin-top: 14px;
    }

    .social-btn {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px;
      border-radius: 11px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(255, 255, 255, 0.03);
      cursor: pointer;
      transition: all .2s ease;
    }

    .social-btn svg {
      width: 18px;
      height: 18px;
    }

    .social-btn:hover {
      border-color: rgba(124, 92, 255, 0.5);
      background: rgba(124, 92, 255, 0.08);
      box-shadow: 0 0 16px rgba(124, 92, 255, 0.25);
      transform: translateY(-1px);
    }

    .signup-line {
      text-align: center;
      margin-top: 24px;
      font-size: 12.5px;
      color: var(--text-2);
    }

    .signup-line a {
      color: var(--text-0);
      font-weight: 600;
      text-decoration: none;
    }

    .signup-line a:hover {
      color: var(--cyan);
    }

    .footer-note {
      text-align: center;
      margin-top: 22px;
      font-size: 10.5px;
      color: var(--text-2);
      font-family: 'JetBrains Mono', monospace;
      letter-spacing: 0.02em;
    }

    @media (max-width:420px) {
      .panel {
        padding: 24px 16px;
      }

      .card {
        padding: 30px 22px 26px;
      }
    }
  </style>
</head>

<body>

  <canvas id="particles"></canvas>

  <div class="shell">

    <!-- ============ LEFT HERO ============ -->
    <div class="hero">
      <div class="brand">
        <div class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" stroke="white" stroke-width="1.6"
              stroke-linejoin="round" />
            <path d="M9 12l2 2 4-4" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <div class="brand-text">
          <span class="name">AI Hub Intelligence</span>
          <span class="tag">Admin Console</span>
        </div>
      </div>

      <div class="hero-copy">
        <div class="eyebrow"><span class="dot"></span> All systems operational</div>
        <h1>Welcome back, <span>Admin.</span></h1>
        <p>Sign in to monitor the AI industry in real time — track tools, models, pricing, and breaking news from a
          single command center.</p>

        <div class="stage">
          <div class="core-glow"></div>
          <svg viewBox="0 0 400 260" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="lineGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#7c5cff" />
                <stop offset="100%" stop-color="#22d3ee" />
              </linearGradient>
            </defs>
            <g stroke="url(#lineGrad)" stroke-width="0.8" opacity="0.55">
              <line x1="200" y1="130" x2="90" y2="60">
                <animate attributeName="opacity" values="0.2;0.7;0.2" dur="3s" repeatCount="indefinite" />
              </line>
              <line x1="200" y1="130" x2="320" y2="55">
                <animate attributeName="opacity" values="0.7;0.2;0.7" dur="3.4s" repeatCount="indefinite" />
              </line>
              <line x1="200" y1="130" x2="60" y2="180">
                <animate attributeName="opacity" values="0.3;0.8;0.3" dur="2.6s" repeatCount="indefinite" />
              </line>
              <line x1="200" y1="130" x2="330" y2="195">
                <animate attributeName="opacity" values="0.6;0.15;0.6" dur="3.8s" repeatCount="indefinite" />
              </line>
              <line x1="200" y1="130" x2="200" y2="30">
                <animate attributeName="opacity" values="0.2;0.6;0.2" dur="2.9s" repeatCount="indefinite" />
              </line>
              <line x1="90" y1="60" x2="200" y2="30" />
              <line x1="320" y1="55" x2="200" y2="30" />
              <line x1="60" y1="180" x2="330" y2="195" opacity="0.25" />
            </g>
            <g fill="#e8e6ff">
              <circle cx="200" cy="130" r="5" fill="#ffffff" />
              <circle cx="90" cy="60" r="3" />
              <circle cx="320" cy="55" r="3" />
              <circle cx="60" cy="180" r="3" />
              <circle cx="330" cy="195" r="3" />
              <circle cx="200" cy="30" r="3" />
            </g>
          </svg>

          <div class="float-card fc1"><b>247 Tools</b>indexed today</div>
          <div class="float-card fc2"><b>+38%</b>search growth</div>
          <div class="float-card fc3"><b>Verified</b>source reliability 94%</div>
          <div class="float-card fc4"><b>12 New</b>breaking articles</div>

          <div class="floor-reflect"></div>
        </div>

        <div class="features">
          <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16v16H4z" stroke-opacity="0" />
              <path d="M4 17V7a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2z" />
              <path d="M4 9h16" />
            </svg><span>Intelligence Command Center</span></div>
          <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 3v18h18" />
              <path d="M7 15l4-4 3 3 5-6" />
            </svg><span>Real-time AI Analytics</span></div>
          <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="9" />
              <path d="M12 7v5l3 3" />
            </svg><span>AI News Monitoring</span></div>
          <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M9 3v18M15 3v18M3 9h18M3 15h18" />
            </svg><span>Tool Comparison Engine</span></div>
          <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="4" y="4" width="7" height="7" rx="1.5" />
              <rect x="13" y="4" width="7" height="7" rx="1.5" />
              <rect x="4" y="13" width="7" height="7" rx="1.5" />
              <rect x="13" y="13" width="7" height="7" rx="1.5" />
            </svg><span>AI Model Management</span></div>
          <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2l8 4v6c0 5-3.4 8.6-8 10-4.6-1.4-8-5-8-10V6l8-4z" />
            </svg><span>Enterprise Security</span></div>
          <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z" />
            </svg><span>Lightning-fast Performance</span></div>
          <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 9a6 6 0 1112 0v3H6V9z" />
              <path d="M4 12h16v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6z" />
            </svg><span>Secure Cloud Platform</span></div>
        </div>
      </div>

      <div style="height:8px;"></div>
    </div>

    <!-- ============ RIGHT LOGIN PANEL ============ -->
    <div class="panel">
      <div class="card">
        <div class="card-mobile-brand">
          <div class="brand-mark">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" stroke="white" stroke-width="1.6"
                stroke-linejoin="round" />
            </svg>
          </div>
          <span class="name">AI Hub Intelligence</span>
        </div>

        <h2>Sign in</h2>
        <p class="sub">Enter your credentials to access the admin console.</p>

        <form action="{{ route('login.authenticate') }}" method="POST">
          @csrf
          <div class="field" id="emailField">
            <label for="email">Email address</label>
            <div class="input-wrap">
              <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M3 6l9 7 9-7" />
                <rect x="3" y="4" width="18" height="16" rx="2" />
              </svg>
              <input
                  type="email"
                  id="email"
                  name="email"
                  value="{{ old('email') }}"
                  placeholder="admin@aihub.io"
                  autocomplete="email"
                  required>          
                
                </div>
            <div class="hint error-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 8v5M12 16h.01" />
              </svg>Enter a valid email address</div>
            <div class="hint success-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6L9 17l-5-5" />
              </svg>Looks good</div>
          </div>

          <div class="field" id="passField">
            <label for="password">Password</label>
            <div class="input-wrap">
              <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect x="4" y="10" width="16" height="10" rx="2" />
                <path d="M8 10V7a4 4 0 018 0v3" />
              </svg>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••••"
                    autocomplete="current-password"
                    required>                
              <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
            <div class="hint error-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 8v5M12 16h.01" />
              </svg>Incorrect password</div>
          </div>

          <div class="row-between">
            <label class="remember"><input
            type="checkbox"
            name="remember"
            {{ old('remember') ? 'checked' : '' }}> Remember me</label>
            <a href="#" class="forgot">Forgot password?</a>
          </div>

          <button type="submit" class="btn-primary" id="signInBtn">
            <span class="btn-label">Sign in</span>
            <span class="spinner"></span>
          </button>
        </form>

        <div class="divider">or continue with</div>

        <div class="social-row">
          <button class="social-btn" aria-label="Continue with Google">
            <svg viewBox="0 0 24 24">
              <path fill="#4285F4"
                d="M23.5 12.27c0-.79-.07-1.54-.2-2.27H12v4.3h6.47a5.53 5.53 0 01-2.4 3.63v3h3.88c2.27-2.09 3.55-5.17 3.55-8.66z" />
              <path fill="#34A853"
                d="M12 24c3.24 0 5.96-1.07 7.95-2.9l-3.88-3a7.4 7.4 0 01-11-3.9H1.1v3.1A12 12 0 0012 24z" />
              <path fill="#FBBC05" d="M5.07 14.2a7.2 7.2 0 010-4.4V6.7H1.1a12 12 0 000 10.6l3.97-3.1z" />
              <path fill="#EA4335"
                d="M12 4.75c1.76 0 3.35.6 4.6 1.8l3.44-3.44C17.95 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.1 6.7l3.97 3.1A7.16 7.16 0 0112 4.75z" />
            </svg>
          </button>
          <button class="social-btn" aria-label="Continue with GitHub">
            <svg viewBox="0 0 24 24" fill="#e8e8ec">
              <path
                d="M12 .3a12 12 0 00-3.8 23.38c.6.1.83-.26.83-.58v-2.02c-3.34.73-4.04-1.6-4.04-1.6-.55-1.4-1.33-1.76-1.33-1.76-1.09-.75.08-.73.08-.73 1.2.08 1.83 1.24 1.83 1.24 1.07 1.82 2.8 1.3 3.49 1 .1-.78.42-1.3.76-1.6-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.12-.3-.54-1.53.12-3.18 0 0 1-.32 3.3 1.23a11.5 11.5 0 016 0c2.28-1.55 3.29-1.23 3.29-1.23.66 1.65.24 2.88.12 3.18.77.84 1.23 1.91 1.23 3.22 0 4.61-2.8 5.63-5.48 5.92.43.37.81 1.1.81 2.22v3.29c0 .32.22.69.83.58A12 12 0 0012 .3z" />
            </svg>
          </button>
          <button class="social-btn" aria-label="Continue with Microsoft">
            <svg viewBox="0 0 24 24">
              <rect x="2" y="2" width="9" height="9" fill="#F35325" />
              <rect x="13" y="2" width="9" height="9" fill="#81BC06" />
              <rect x="2" y="13" width="9" height="9" fill="#05A6F0" />
              <rect x="13" y="13" width="9" height="9" fill="#FFBA08" />
            </svg>
          </button>
        </div>

        <div class="signup-line">Don't have an account? <a href="/auth/signup">Create admin account</a></div>
        <div class="footer-note">AI HUB INTELLIGENCE © 2026</div>
      </div>
    </div>

  </div>

  <script>
    // ---------- Particle background ----------
    const canvas = document.getElementById('particles');
    const ctx = canvas.getContext('2d');
    let w, h, particles;

    function resize() {
      w = canvas.width = window.innerWidth;
      h = canvas.height = window.innerHeight;
    }
    function initParticles() {
      const count = Math.min(70, Math.floor((w * h) / 22000));
      particles = Array.from({ length: count }, () => ({
        x: Math.random() * w,
        y: Math.random() * h,
        r: Math.random() * 1.6 + 0.4,
        vx: (Math.random() - 0.5) * 0.15,
        vy: (Math.random() - 0.5) * 0.15,
        hue: Math.random() > 0.5 ? '124,92,255' : '34,211,238',
        a: Math.random() * 0.5 + 0.15
      }));
    }
    function tick() {
      ctx.clearRect(0, 0, w, h);
      particles.forEach(p => {
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0) p.x = w; if (p.x > w) p.x = 0;
        if (p.y < 0) p.y = h; if (p.y > h) p.y = 0;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${p.hue},${p.a})`;
        ctx.fill();
      });
      requestAnimationFrame(tick);
    }
    window.addEventListener('resize', () => { resize(); initParticles(); });
    resize(); initParticles(); tick();

    // ---------- Password visibility ----------
    const pass = document.getElementById('password');
    const toggle = document.getElementById('togglePass');
    toggle.addEventListener('click', () => {
      const show = pass.type === 'password';
      pass.type = show ? 'text' : 'password';
      toggle.innerHTML = show
        ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 002.8 2.8"/><path d="M9.3 5.3A11 11 0 0123 12s-1.6 2.8-4.3 4.7M6.1 6.1C3.4 7.9 1 12 1 12s4 7 11 7c1.6 0 3-.3 4.3-.9"/></svg>'
        : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
    });

    // ---------- Simple validation + loading demo ----------
    const emailField = document.getElementById('emailField');
    const passField = document.getElementById('passField');
    const emailInput = document.getElementById('email');
    const form = document.getElementById('loginForm');
    const btn = document.getElementById('signInBtn');

    emailInput.addEventListener('blur', () => {
      const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value);
      emailField.classList.remove('error', 'success');
      if (emailInput.value.length === 0) return;
      emailField.classList.add(valid ? 'success' : 'error');
    });

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      passField.classList.remove('error');
      btn.classList.add('loading');
      btn.disabled = true;
      setTimeout(() => {
        btn.classList.remove('loading');
        btn.disabled = false;
      }, 1800);
    });
  </script>

</body>

</html>