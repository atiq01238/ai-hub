<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Hub Intelligence — Create Admin Account</title>
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
            --warning: #ffb648;
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

        #particles {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

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

        /* ---------- Hero ---------- */
        .hero {
            position: relative;
            padding: 56px 64px 40px;
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
            background: linear-gradient(180deg, transparent, rgba(34, 211, 238, 0.08));
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
            font-size: clamp(28px, 2.8vw, 40px);
            line-height: 1.14;
            letter-spacing: -0.01em;
            max-width: 520px;
        }

        .hero h1 span {
            background: linear-gradient(120deg, var(--cyan), var(--violet) 70%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .hero p {
            margin-top: 16px;
            max-width: 440px;
            color: var(--text-1);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ---------- Security hologram stage ---------- */
        .stage {
            position: relative;
            height: 250px;
            margin: 30px 0 6px;
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
            width: 170px;
            height: 170px;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(34, 211, 238, 0.4), rgba(124, 92, 255, 0.14) 55%, transparent 72%);
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
            top: 4%;
            left: 0%;
            animation-delay: 0s;
        }

        .fc2 {
            top: 10%;
            right: 2%;
            animation-delay: 1.2s;
        }

        .fc3 {
            bottom: 10%;
            left: 6%;
            animation-delay: 2.1s;
        }

        .fc4 {
            bottom: 0%;
            right: 8%;
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
            background: linear-gradient(90deg, transparent, rgba(34, 211, 238, 0.5), rgba(124, 92, 255, 0.4), transparent);
            filter: blur(10px);
            opacity: 0.6;
        }

        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 22px;
            z-index: 2;
            margin-top: 6px;
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

        /* ---------- Right: signup panel ---------- */
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
            max-width: 420px;
            background: linear-gradient(160deg, rgba(255, 255, 255, 0.055), rgba(255, 255, 255, 0.02));
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 36px 34px 28px;
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
            background: linear-gradient(135deg, rgba(34, 211, 238, 0.5), rgba(124, 92, 255, 0.15), transparent 60%);
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
            margin-bottom: 22px;
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
            font-size: 21px;
            font-weight: 600;
            letter-spacing: -0.01em;
        }

        .card .sub {
            margin-top: 6px;
            font-size: 12.5px;
            color: var(--text-2);
        }

        form {
            margin-top: 22px;
            display: flex;
            flex-direction: column;
            gap: 14px;
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
            padding: 11px 14px 11px 40px;
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

        .field-status {
            position: absolute;
            right: 12px;
            width: 16px;
            height: 16px;
            display: none;
        }

        .field.success .field-status.ok {
            display: block;
            color: var(--success);
        }

        .field.error .field-status.bad {
            display: block;
            color: var(--danger);
        }

        .hint {
            font-size: 10.5px;
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

        .field.has-status .toggle-pass {
            right: 34px;
        }

        /* ---------- Password strength ---------- */
        .strength-wrap {
            margin-top: 2px;
        }

        .strength-bar {
            height: 5px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.08);
            overflow: hidden;
            display: flex;
            gap: 3px;
        }

        .strength-bar .seg {
            flex: 1;
            height: 100%;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 3px;
            transition: background .3s ease;
        }

        .strength-label {
            display: flex;
            justify-content: space-between;
            font-size: 10.5px;
            color: var(--text-2);
            margin-top: 6px;
            font-family: 'JetBrains Mono', monospace;
        }

        .strength-label .level {
            font-weight: 600;
        }

        .req-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px 10px;
            margin-top: 10px;
        }

        .req {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: var(--text-2);
            transition: color .25s ease;
        }

        .req .tick {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 1.4px solid rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all .25s ease;
        }

        .req .tick svg {
            width: 8px;
            height: 8px;
            opacity: 0;
            transform: scale(0.5);
            transition: all .2s ease;
        }

        .req.met {
            color: var(--text-0);
        }

        .req.met .tick {
            background: var(--success);
            border-color: var(--success);
            box-shadow: 0 0 8px rgba(55, 230, 176, 0.5);
        }

        .req.met .tick svg {
            opacity: 1;
            transform: scale(1);
        }

        /* ---------- Terms / captcha ---------- */
        .terms {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            font-size: 11.5px;
            color: var(--text-1);
            margin-top: 2px;
            line-height: 1.5;
        }

        .terms input {
            appearance: none;
            width: 15px;
            height: 15px;
            border-radius: 5px;
            margin-top: 1px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            background: rgba(255, 255, 255, 0.03);
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            transition: all .15s;
        }

        .terms input:checked {
            background: linear-gradient(135deg, var(--violet), var(--cyan));
            border-color: transparent;
        }

        .terms input:checked::after {
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

        .terms a {
            color: var(--cyan);
            text-decoration: none;
            font-weight: 500;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        .captcha {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: rgba(255, 255, 255, 0.035);
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 11px;
            padding: 11px 14px;
        }

        .captcha-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .captcha-box {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            border: 1.4px solid rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.03);
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
            transition: all .2s;
        }

        .captcha-box.checked {
            background: linear-gradient(135deg, var(--violet), var(--cyan));
            border-color: transparent;
        }

        .captcha-box.checked::after {
            content: "";
            position: absolute;
            left: 5.5px;
            top: 2px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 1.6px 1.6px 0;
            transform: rotate(45deg);
        }

        .captcha span {
            font-size: 12.5px;
            color: var(--text-1);
        }

        .captcha-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 9.5px;
            color: var(--text-2);
            letter-spacing: 0.04em;
        }

        .captcha-badge svg {
            width: 13px;
            height: 13px;
            color: var(--cyan);
        }

        .btn-primary {
            margin-top: 4px;
            position: relative;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--cyan), var(--indigo) 55%, var(--violet));
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
            overflow: hidden;
            isolation: isolate;
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

        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.45);
            transform: scale(0);
            animation: ripple .6s ease-out;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes ripple {
            to {
                transform: scale(3.5);
                opacity: 0;
            }
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0 4px;
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
            margin-top: 22px;
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
            margin-top: 20px;
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
                padding: 28px 20px 24px;
            }

            .req-list {
                grid-template-columns: 1fr;
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
                        <path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" stroke="white"
                            stroke-width="1.6" stroke-linejoin="round" />
                        <path d="M9 12l2 2 4-4" stroke="white" stroke-width="1.6" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="brand-text">
                    <span class="name">AI Hub Intelligence</span>
                    <span class="tag">Admin Console</span>
                </div>
            </div>

            <div class="hero-copy">
                <div class="eyebrow"><span class="dot"></span> Encrypted registration channel</div>
                <h1>Provision your <span>admin identity.</span></h1>
                <p>Create a secured administrator account to manage AI tools, models, pricing, and intelligence across
                    the entire platform.</p>

                <div class="stage">
                    <div class="core-glow"></div>
                    <svg viewBox="0 0 400 250" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="lineGrad2" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#22d3ee" />
                                <stop offset="100%" stop-color="#7c5cff" />
                            </linearGradient>
                        </defs>
                        <!-- shield outline -->
                        <path d="M200 40 L255 62 V118 C255 158 231 186 200 198 C169 186 145 158 145 118 V62 Z"
                            fill="none" stroke="url(#lineGrad2)" stroke-width="1.4" opacity="0.85" />
                        <path d="M178 118 l16 16 30 -34" fill="none" stroke="#e8f9ff" stroke-width="2.2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <animate attributeName="opacity" values="0.4;1;0.4" dur="2.4s" repeatCount="indefinite" />
                        </path>
                        <g stroke="url(#lineGrad2)" stroke-width="0.7" opacity="0.4">
                            <line x1="200" y1="118" x2="70" y2="70">
                                <animate attributeName="opacity" values="0.15;0.6;0.15" dur="3.2s"
                                    repeatCount="indefinite" />
                            </line>
                            <line x1="200" y1="118" x2="340" y2="60">
                                <animate attributeName="opacity" values="0.6;0.15;0.6" dur="3.6s"
                                    repeatCount="indefinite" />
                            </line>
                            <line x1="200" y1="118" x2="60" y2="190">
                                <animate attributeName="opacity" values="0.2;0.55;0.2" dur="2.8s"
                                    repeatCount="indefinite" />
                            </line>
                            <line x1="200" y1="118" x2="335" y2="200">
                                <animate attributeName="opacity" values="0.5;0.1;0.5" dur="4s"
                                    repeatCount="indefinite" />
                            </line>
                        </g>
                        <g fill="#dff7ff">
                            <circle cx="70" cy="70" r="2.6" />
                            <circle cx="340" cy="60" r="2.6" />
                            <circle cx="60" cy="190" r="2.6" />
                            <circle cx="335" cy="200" r="2.6" />
                        </g>
                        <!-- small orbit cubes -->
                        <g stroke="#7c5cff" stroke-width="1" fill="rgba(124,92,255,0.12)">
                            <rect x="30" y="120" width="12" height="12" rx="2" transform="rotate(20 36 126)">
                                <animateTransform attributeName="transform" type="rotate" from="0 36 126"
                                    to="360 36 126" dur="14s" repeatCount="indefinite" />
                            </rect>
                            <rect x="352" y="130" width="10" height="10" rx="2" transform="rotate(-15 357 135)">
                                <animateTransform attributeName="transform" type="rotate" from="360 357 135"
                                    to="0 357 135" dur="11s" repeatCount="indefinite" />
                            </rect>
                        </g>
                    </svg>

                    <div class="float-card fc1"><b>256-bit</b>encryption active</div>
                    <div class="float-card fc2"><b>Synced</b>secure cloud storage</div>
                    <div class="float-card fc3"><b>MFA Ready</b>identity protection</div>
                    <div class="float-card fc4"><b>99.98%</b>platform uptime</div>

                    <div class="floor-reflect"></div>
                </div>

                <div class="features">
                    <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16v16H4z" stroke-opacity="0" />
                            <rect x="4" y="4" width="16" height="16" rx="3" />
                        </svg><span>Full Platform Access</span></div>
                    <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="4" y="4" width="7" height="7" rx="1.5" />
                            <rect x="13" y="4" width="7" height="7" rx="1.5" />
                            <rect x="4" y="13" width="7" height="7" rx="1.5" />
                            <rect x="13" y="13" width="7" height="7" rx="1.5" />
                        </svg><span>AI Tool Management</span></div>
                    <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 3" />
                        </svg><span>AI News Intelligence</span></div>
                    <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 3v18M15 3v18M3 9h18M3 15h18" />
                        </svg><span>Comparison Engine</span></div>
                    <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 3v18h18" />
                            <path d="M7 15l4-4 3 3 5-6" />
                        </svg><span>Analytics Dashboard</span></div>
                    <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l8 4v6c0 5-3.4 8.6-8 10-4.6-1.4-8-5-8-10V6l8-4z" />
                        </svg><span>Enterprise Security</span></div>
                    <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 9a6 6 0 1112 0v3H6V9z" />
                            <path d="M4 12h16v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6z" />
                        </svg><span>Secure Cloud Infrastructure</span></div>
                    <div class="feature"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z" />
                        </svg><span>Unlimited Scalability</span></div>
                </div>
            </div>

            <div style="height:8px;"></div>
        </div>

        <!-- ============ RIGHT SIGNUP PANEL ============ -->
        <div class="panel">
            <div class="card">
                <div class="card-mobile-brand">
                    <div class="brand-mark">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L4 6v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10V6l-8-4z" stroke="white"
                                stroke-width="1.6" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <span class="name">AI Hub Intelligence</span>
                </div>

                <h2>Create your admin account</h2>
                <p class="sub">Set up secure access to the intelligence platform.</p>

                <form  action="{{ route('signup.store') }}" method="POST">
                    @csrf
                    <div class="field" id="nameField">
                        <label for="fullname">Full name</label>
                        <div class="input-wrap">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="8" r="3.5" />
                                <path d="M5 20c0-4 3.5-6.5 7-6.5s7 2.5 7 6.5" />
                            </svg>
                            <input
                                type="text"
                                id="fullname"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Ayesha Khan"
                                autocomplete="name"
                                required>
                        </div>
                    </div>

                    <div class="field has-status" id="emailField">
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
                            <svg class="field-status ok" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>
                            <svg class="field-status bad" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.2">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v5M12 16h.01" />
                            </svg>
                        </div>
                        <div class="hint error-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v5M12 16h.01" />
                            </svg>Enter a valid email address</div>
                        <div class="hint success-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
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
                                    placeholder="Create a strong password"
                                    autocomplete="new-password"
                                    required>                                
                            <button type="button" class="toggle-pass" id="togglePass1" aria-label="Show password">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>

                        <div class="strength-wrap">
                            <div class="strength-bar">
                                <div class="seg" id="seg1"></div>
                                <div class="seg" id="seg2"></div>
                                <div class="seg" id="seg3"></div>
                                <div class="seg" id="seg4"></div>
                            </div>
                            <div class="strength-label"><span>Password strength</span><span class="level"
                                    id="strengthLevel">—</span></div>
                        </div>

                        <div class="req-list">
                            <div class="req" id="reqLen"><span class="tick"><svg viewBox="0 0 24 24" fill="none"
                                        stroke="white" stroke-width="3">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg></span>8+ characters</div>
                            <div class="req" id="reqUpper"><span class="tick"><svg viewBox="0 0 24 24" fill="none"
                                        stroke="white" stroke-width="3">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg></span>Uppercase letter</div>
                            <div class="req" id="reqLower"><span class="tick"><svg viewBox="0 0 24 24" fill="none"
                                        stroke="white" stroke-width="3">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg></span>Lowercase letter</div>
                            <div class="req" id="reqNum"><span class="tick"><svg viewBox="0 0 24 24" fill="none"
                                        stroke="white" stroke-width="3">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg></span>Number</div>
                            <div class="req" id="reqSpecial"><span class="tick"><svg viewBox="0 0 24 24" fill="none"
                                        stroke="white" stroke-width="3">
                                        <path d="M20 6L9 17l-5-5" />
                                    </svg></span>Special character</div>
                        </div>
                    </div>

                    <div class="field" id="confirmField">
                        <label for="confirm">Confirm password</label>
                        <div class="input-wrap">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <rect x="4" y="10" width="16" height="10" rx="2" />
                                <path d="M8 10V7a4 4 0 018 0v3" />
                            </svg>
                                <input
                                    type="password"
                                    id="confirm"
                                    name="password_confirmation"
                                    placeholder="Re-enter password"
                                    autocomplete="new-password"
                                    required>                          
                            <button type="button" class="toggle-pass" id="togglePass2" aria-label="Show password">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        <div class="hint error-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="9" />
                                <path d="M12 8v5M12 16h.01" />
                            </svg>Passwords do not match</div>
                        <div class="hint success-hint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M20 6L9 17l-5-5" />
                            </svg>Passwords match</div>
                    </div>

                    <div class="captcha">
                        <div class="captcha-left">
                            <div class="captcha-box" id="captchaBox"></div>
                            <span>I'm not a robot</span>
                        </div>
                        <div class="captcha-badge">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l8 4v6c0 5-3.4 8.6-8 10-4.6-1.4-8-5-8-10V6l8-4z" />
                            </svg>
                            SECURE-CHECK
                        </div>
                    </div>

                    <label class="terms">
                        <input
                            type="checkbox"
                            id="termsCheck"
                            name="terms"
                            required>                        
                        <span>I agree to the <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a>
                            of AI Hub Intelligence.</span>
                    </label>

                    <button type="submit" class="btn-primary" id="signUpBtn">
                        <span class="btn-label">Create account</span>
                        <span class="spinner"></span>
                    </button>
                </form>

                <div class="divider">or sign up with</div>

                <div class="social-row">
                    <button class="social-btn" aria-label="Sign up with Google">
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
                    <button class="social-btn" aria-label="Sign up with GitHub">
                        <svg viewBox="0 0 24 24" fill="#e8e8ec">
                            <path
                                d="M12 .3a12 12 0 00-3.8 23.38c.6.1.83-.26.83-.58v-2.02c-3.34.73-4.04-1.6-4.04-1.6-.55-1.4-1.33-1.76-1.33-1.76-1.09-.75.08-.73.08-.73 1.2.08 1.83 1.24 1.83 1.24 1.07 1.82 2.8 1.3 3.49 1 .1-.78.42-1.3.76-1.6-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.12-.3-.54-1.53.12-3.18 0 0 1-.32 3.3 1.23a11.5 11.5 0 016 0c2.28-1.55 3.29-1.23 3.29-1.23.66 1.65.24 2.88.12 3.18.77.84 1.23 1.91 1.23 3.22 0 4.61-2.8 5.63-5.48 5.92.43.37.81 1.1.81 2.22v3.29c0 .32.22.69.83.58A12 12 0 0012 .3z" />
                        </svg>
                    </button>
                    <button class="social-btn" aria-label="Sign up with Microsoft">
                        <svg viewBox="0 0 24 24">
                            <rect x="2" y="2" width="9" height="9" fill="#F35325" />
                            <rect x="13" y="2" width="9" height="9" fill="#81BC06" />
                            <rect x="2" y="13" width="9" height="9" fill="#05A6F0" />
                            <rect x="13" y="13" width="9" height="9" fill="#FFBA08" />
                        </svg>
                    </button>
                </div>

                <div class="signup-line">Already have an account? <a href="/auth/login">Sign in</a></div>
                <div class="footer-note">AI HUB INTELLIGENCE © 2026</div>
            </div>
        </div>

    </div>

    <script>
        // ---------- Particle background ----------
        const canvas = document.getElementById('particles');
        const ctx = canvas.getContext('2d');
        let w, h, particles;
        function resize() { w = canvas.width = window.innerWidth; h = canvas.height = window.innerHeight; }
        function initParticles() {
            const count = Math.min(70, Math.floor((w * h) / 22000));
            particles = Array.from({ length: count }, () => ({
                x: Math.random() * w, y: Math.random() * h, r: Math.random() * 1.6 + 0.4,
                vx: (Math.random() - 0.5) * 0.15, vy: (Math.random() - 0.5) * 0.15,
                hue: Math.random() > 0.5 ? '34,211,238' : '124,92,255',
                a: Math.random() * 0.5 + 0.15
            }));
        }
        function tick() {
            ctx.clearRect(0, 0, w, h);
            particles.forEach(p => {
                p.x += p.vx; p.y += p.vy;
                if (p.x < 0) p.x = w; if (p.x > w) p.x = 0;
                if (p.y < 0) p.y = h; if (p.y > h) p.y = 0;
                ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(${p.hue},${p.a})`; ctx.fill();
            });
            requestAnimationFrame(tick);
        }
        window.addEventListener('resize', () => { resize(); initParticles(); });
        resize(); initParticles(); tick();

        // ---------- Password toggles ----------
        function wireToggle(btnId, inputId) {
            const btn = document.getElementById(btnId);
            const input = document.getElementById(inputId);
            btn.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = show
                    ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l18 18"/><path d="M10.6 10.6a2 2 0 002.8 2.8"/><path d="M9.3 5.3A11 11 0 0123 12s-1.6 2.8-4.3 4.7M6.1 6.1C3.4 7.9 1 12 1 12s4 7 11 7c1.6 0 3-.3 4.3-.9"/></svg>'
                    : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>';
            });
        }
        wireToggle('togglePass1', 'password');
        wireToggle('togglePass2', 'confirm');

        // ---------- Email validation ----------
        const emailField = document.getElementById('emailField');
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('blur', () => {
            emailField.classList.remove('error', 'success');
            if (emailInput.value.length === 0) return;
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value);
            emailField.classList.add(valid ? 'success' : 'error');
        });

        // ---------- Password strength ----------
        const passInput = document.getElementById('password');
        const segEls = [document.getElementById('seg1'), document.getElementById('seg2'), document.getElementById('seg3'), document.getElementById('seg4')];
        const strengthLevel = document.getElementById('strengthLevel');
        const reqs = {
            reqLen: v => v.length >= 8,
            reqUpper: v => /[A-Z]/.test(v),
            reqLower: v => /[a-z]/.test(v),
            reqNum: v => /[0-9]/.test(v),
            reqSpecial: v => /[^A-Za-z0-9]/.test(v)
        };
        const levelMeta = [
            { label: '—', color: 'rgba(255,255,255,0.08)' },
            { label: 'Weak', color: 'var(--danger)' },
            { label: 'Fair', color: 'var(--warning)' },
            { label: 'Good', color: '#22d3ee' },
            { label: 'Strong', color: 'var(--success)' }
        ];

        passInput.addEventListener('input', () => {
            const v = passInput.value;
            let metCount = 0;
            Object.entries(reqs).forEach(([id, test]) => {
                const el = document.getElementById(id);
                const met = test(v);
                el.classList.toggle('met', met);
                if (met) metCount++;
            });
            const score = Math.min(4, Math.round((metCount / 5) * 4));
            segEls.forEach((seg, i) => {
                seg.style.background = i < score ? levelMeta[score].color.replace('var(--danger)', '#ff5c7a').replace('var(--warning)', '#ffb648').replace('var(--success)', '#37e6b0') : 'rgba(255,255,255,0.08)';
            });
            strengthLevel.textContent = v.length === 0 ? '—' : levelMeta[score].label;
            strengthLevel.style.color = v.length === 0 ? 'var(--text-2)' : (score <= 1 ? '#ff5c7a' : score === 2 ? '#ffb648' : score === 3 ? '#22d3ee' : '#37e6b0');
            checkConfirm();
        });

        // ---------- Confirm password match ----------
        const confirmField = document.getElementById('confirmField');
        const confirmInput = document.getElementById('confirm');
        function checkConfirm() {
            if (confirmInput.value.length === 0) { confirmField.classList.remove('error', 'success'); return; }
            confirmField.classList.remove('error', 'success');
            confirmField.classList.add(confirmInput.value === passInput.value ? 'success' : 'error');
        }
        confirmInput.addEventListener('input', checkConfirm);

        // ---------- Captcha ----------
        const captchaBox = document.getElementById('captchaBox');
        captchaBox.addEventListener('click', () => captchaBox.classList.toggle('checked'));

        // ---------- Submit with ripple + loading ----------
        const form = document.getElementById('signupForm');
        const btn = document.getElementById('signUpBtn');
        btn.addEventListener('click', (e) => {
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            btn.appendChild(ripple);
            setTimeout(() => ripple.remove(), 650);
        });
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            btn.classList.add('loading');
            btn.disabled = true;
            setTimeout(() => { btn.classList.remove('loading'); btn.disabled = false; }, 1800);
        });
    </script>

</body>

</html>