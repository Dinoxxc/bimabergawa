<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign in - PT. Panca Bima Bergawa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        *, ::before, ::after { box-sizing: border-box; margin: 0; padding: 0; }
        
        :root {
            --bg-main: #09090b;
            --bg-canvas: #ffffff;
            --bg-subtle: #fafafa;
            --border-light: #e4e4e7;
            --border-dark: #27272a;
            --border-focus: #09090b;
            --text-heading: #09090b;
            --text-body: #27272a;
            --text-muted: #71717a;
            --accent: #18181b;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-body);
            min-height: 100vh;
            margin: 0;
            display: flex;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .split-layout {
            display: flex;
            width: 100vw;
            min-height: 100vh;
        }

        /* 1. Left Stage - High-Tech Industrial Showcase */
        .stage-showcase {
            flex: 1.15;
            background: linear-gradient(135deg, #061527 0%, #0c2646 25%, #153e6b 50%, #1e5a99 75%, #0a213d 100%);
            background-size: 260% 260%;
            animation: blueGradientFlow 16s ease-in-out infinite alternate;
            color: #ffffff;
            padding: 56px 64px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            border-right: 1px solid rgba(255, 255, 255, 0.12);
            overflow: hidden;
        }

        .stage-showcase::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 28px 28px;
            pointer-events: none;
            z-index: 1;
        }

        .stage-showcase::before {
            content: '';
            position: absolute;
            top: -120px;
            left: -120px;
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, rgba(76, 165, 255, 0.14) 0%, transparent 70%);
            pointer-events: none;
            z-index: 1;
            animation: pulseAmbient 12s ease-in-out infinite alternate;
        }

        @keyframes blueGradientFlow {
            0% { background-position: 0% 20%; }
            50% { background-position: 100% 80%; }
            100% { background-position: 20% 100%; }
        }

        @keyframes pulseAmbient {
            0% { transform: scale(1) translate(0, 0); opacity: 0.6; }
            100% { transform: scale(1.15) translate(30px, 20px); opacity: 0.95; }
        }

        .brand-emblem-box,
        .showcase-center,
        .showcase-bottom {
            position: relative;
            z-index: 2;
        }

        .brand-emblem-box {
            display: flex;
            align-items: center;
        }

        .brand-logo-white {
            height: 48px;
            width: auto;
            max-width: 320px;
            object-fit: contain;
            filter: drop-shadow(0 2px 10px rgba(0, 0, 0, 0.3));
        }

        .showcase-center {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-width: 620px;
            margin: 32px 0;
        }

        .showcase-headline {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(38px, 4vw, 54px);
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -0.03em;
            color: #ffffff;
            margin: 0;
            min-height: 200px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            word-break: break-word;
            text-shadow: 0 2px 12px rgba(6, 21, 39, 0.4);
        }

        .showcase-bottom {
            margin-top: auto;
            padding-top: 28px;
        }

        .showcase-icons-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }

        .tech-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(8px);
        }

        .tech-icon-badge:hover {
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.4);
            background-color: rgba(255, 255, 255, 0.16);
            transform: translateY(-2px);
        }

        .showcase-description {
            font-size: 13.5px;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.8);
            max-width: 520px;
            margin-bottom: 24px;
        }

        .showcase-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.55);
            border-top: 1px solid rgba(255, 255, 255, 0.14);
            padding-top: 20px;
        }

        /* 2. Right Stage - Full Viewport Architectural Canvas */
        .stage-interaction {
            flex: 1.15;
            background-color: #fafbfc;
            background-image: radial-gradient(circle, #cbd5e1 0.85px, transparent 0.85px);
            background-size: 28px 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            position: relative;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Precision CAD Registration Corner Reticles */
        .cad-mark {
            position: absolute;
            width: 24px;
            height: 24px;
            pointer-events: none;
            z-index: 2;
            opacity: 0.85;
        }

        .cad-mark-tl { top: 24px; left: 24px; }
        .cad-mark-tr { top: 24px; right: 24px; }
        .cad-mark-bl { bottom: 24px; left: 24px; }
        .cad-mark-br { bottom: 24px; right: 24px; }

        /* Engineering Blueprint Vector Motifs */
        .geom-motif {
            position: absolute;
            pointer-events: none;
            z-index: 1;
            user-select: none;
        }

        .geom-motif-tr {
            top: 24px;
            right: 24px;
        }

        .geom-motif-bl {
            bottom: 24px;
            left: 24px;
        }

        .geom-scale-right {
            position: absolute;
            right: 24px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            z-index: 2;
        }

        /* Center Interaction Stage */
        .interaction-center-stage {
            width: 100%;
            max-width: 440px;
            margin: auto;
            position: relative;
            z-index: 10;
        }

        .interaction-title {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #0c2646;
            margin-bottom: 8px;
        }

        .interaction-subtitle {
            font-size: 14.5px;
            color: #64748b;
            line-height: 1.55;
            margin-bottom: 28px;
        }

        .interaction-subtitle strong {
            color: #0c2646;
            font-weight: 600;
        }

        /* High-End Corporate Toast Notification System (Theme Matched: Deep Navy #0c2646) */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
            width: calc(100vw - 48px);
            pointer-events: none;
        }

        .toast-item {
            pointer-events: auto;
            background: #0c2646;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 8px;
            padding: 14px 18px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 16px 36px -4px rgba(4, 15, 30, 0.4), 0 4px 12px rgba(0, 0, 0, 0.25);
            transform: translateY(16px);
            opacity: 0;
            animation: toastSlideUp 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            transition: all 0.25s ease;
        }

        .toast-item.toast-closing {
            opacity: 0;
            transform: translateY(12px) scale(0.96);
        }

        .toast-content {
            flex: 1;
            font-size: 13px;
            line-height: 1.45;
            color: #cbd5e1;
            word-break: break-word;
        }

        .toast-title {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 3px;
            font-family: 'Outfit', sans-serif;
            font-size: 13.5px;
            letter-spacing: 0.01em;
        }

        .toast-message {
            color: #cbd5e1;
            font-family: 'Inter', sans-serif;
        }

        .toast-close-btn {
            flex-shrink: 0;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 2px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s ease;
        }

        .toast-close-btn:hover {
            color: #ffffff;
        }

        @keyframes toastSlideUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading Spinner Animation */
        .spin-animation {
            animation: rotateSpinner 0.8s linear infinite;
        }

        @keyframes rotateSpinner {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Transition Animations */
        .auth-transition-stage {
            animation: fadeInStage 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeInStage {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shakeX {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .shake {
            animation: shakeX 0.4s ease;
        }

        .btn-circle-submit:disabled {
            opacity: 0.65;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        .form-field-group {
            margin-bottom: 22px;
        }

        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .input-wrapper-relative {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 14px;
            color: #64748b;
            display: flex;
            align-items: center;
            pointer-events: none;
            transition: color 0.15s ease;
        }

        .input-wrapper-relative:focus-within .input-icon-left {
            color: #1e5a99;
        }

        .input-styled-text {
            width: 100%;
            height: 48px;
            padding: 0 14px 0 44px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #0f172a;
            background-color: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            transition: all 0.18s ease;
        }

        .input-styled-text:focus {
            border-color: #46a2da;
            box-shadow: 0 0 0 3.5px rgba(70, 162, 218, 0.18);
        }

        /* 6-Digit OTP Boxes */
        .otp-inputs-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 8px;
            margin-bottom: 22px;
        }

        .otp-digit-box {
            width: 100%;
            height: 60px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 26px;
            font-weight: 700;
            text-align: center;
            color: #0c2646;
            background-color: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            transition: all 0.18s ease;
        }

        .otp-digit-box:focus {
            border-color: #46a2da;
            background-color: #ffffff;
            box-shadow: 0 0 0 3.5px rgba(70, 162, 218, 0.2);
            transform: translateY(-2px);
        }

        /* High-End Precision Circular Submit Button (#46a2da) */
        .submit-action-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .btn-circle-submit {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #46a2da;
            color: #ffffff;
            border: 1px solid #3692ca;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 2px 8px rgba(70, 162, 218, 0.25);
            outline: none;
            flex-shrink: 0;
        }

        .btn-circle-submit:hover {
            background-color: #3692ca;
            border-color: #2b84bd;
            transform: translateY(-2px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3), 0 6px 18px rgba(70, 162, 218, 0.35);
        }

        .btn-circle-submit:active {
            background-color: #2674a6;
            transform: translateY(0);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-circle-submit:focus-visible {
            box-shadow: 0 0 0 3px rgba(70, 162, 218, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .btn-circle-submit svg {
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-circle-submit:hover svg {
            transform: translateX(2.5px);
        }

        /* High-End Architectural Simplicity CTA Button (#46a2da) */
        .btn-blue-cta,
        .btn-black-cta {
            width: 100%;
            height: 46px;
            background-color: #46a2da;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.015em;
            border: 1px solid #3692ca;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.22), 0 1px 2px rgba(70, 162, 218, 0.15);
        }

        .btn-blue-cta:hover,
        .btn-black-cta:hover {
            background-color: #3692ca;
            border-color: #2b84bd;
            transform: translateY(-1px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.3), 0 4px 12px rgba(70, 162, 218, 0.25);
        }

        .btn-blue-cta:active,
        .btn-black-cta:active {
            background-color: #2674a6;
            transform: translateY(0);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-blue-cta svg {
            transition: transform 0.18s ease;
        }

        .btn-blue-cta:hover svg {
            transform: translateX(3px);
        }

        .btn-resend-clean {
            background: none;
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: #46a2da;
            cursor: pointer;
            padding: 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            transition: all 0.15s ease;
            margin-top: 14px;
        }

        .btn-resend-clean:hover:not(:disabled) {
            color: #2b84bd;
            text-decoration: underline;
        }

        .btn-resend-clean:disabled {
            cursor: not-allowed;
            opacity: 0.55;
            color: #94a3b8;
        }

        .change-email-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            text-decoration: none;
            margin-top: 10px;
            transition: color 0.15s ease;
        }

        .change-email-link:hover {
            color: #0c2646;
        }

        /* Responsive Breakpoint */
        @media (max-width: 1024px) {
            .split-layout {
                flex-direction: column;
            }
            .stage-showcase {
                padding: 40px 24px;
                flex: none;
            }
            .stage-interaction {
                padding: 48px 24px;
                flex: none;
                min-height: auto;
            }
            .geom-scale-right {
                display: none;
            }
            .showcase-headline {
                font-size: 32px;
            }
        }

        @media (max-width: 640px) {
            .sectors-grid-compact {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="split-layout">
        
        <!-- LEFT: Technical & Industrial Corporate Showcase -->
        <section class="stage-showcase">
            <!-- Top: Brand Header with All-White Logo -->
            <div class="brand-emblem-box">
                <img src="{{ asset('images/logo-pbb-white.png') }}" alt="PT. Panca Bima Bergawa" class="brand-logo-white">
            </div>

            <!-- Middle: Vertically Centered Typewriter Headline with Looping Motivation Sentences -->
            <div class="showcase-center">
                <h1 class="showcase-headline" aria-label="Panca Bima Bergawa Enterprise Resource Planning.">
                    <span id="typewriter-target"></span>
                </h1>
            </div>

            <!-- Bottom: Icons Row, Minimalist Description & Footer -->
            <div class="showcase-bottom">
                <div class="showcase-icons-row">
                    <div class="tech-icon-badge" title="Security Architecture">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </div>
                    <div class="tech-icon-badge" title="Relational Data Hub">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <ellipse cx="12" cy="5" rx="9" ry="3"/>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                        </svg>
                    </div>
                    <div class="tech-icon-badge" title="Network Infrastructure">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/>
                            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"/>
                            <line x1="6" y1="6" x2="6.01" y2="6"/>
                            <line x1="6" y1="18" x2="6.01" y2="18"/>
                        </svg>
                    </div>
                    <div class="tech-icon-badge" title="Cryptographic Authentication">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                </div>

                <p class="showcase-description">
                    Integrated operational governance and commercial procurement management system for PT. Panca Bima Bergawa.
                </p>

                <footer class="showcase-footer">
                    <div>&copy; 2026 PT. Panca Bima Bergawa. All rights reserved.</div>
                    <div style="font-family: 'JetBrains Mono', monospace;">Version v1.5.0-ERP</div>
                </footer>
            </div>
        </section>


        <!-- RIGHT: Full Viewport Architectural Canvas with Geometric Accents -->
        <main class="stage-interaction">
            
            <!-- Precision CAD Registration Marks -->
            <div class="cad-mark cad-mark-tl" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.2">
                    <path d="M2 10V2h8"/>
                    <circle cx="2" cy="2" r="1.2" fill="#94a3b8"/>
                </svg>
            </div>
            <div class="cad-mark cad-mark-tr" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.2">
                    <path d="M22 10V2h-8"/>
                    <circle cx="22" cy="2" r="1.2" fill="#94a3b8"/>
                </svg>
            </div>
            <div class="cad-mark cad-mark-bl" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.2">
                    <path d="M2 14v8h8"/>
                    <circle cx="2" cy="22" r="1.2" fill="#94a3b8"/>
                </svg>
            </div>
            <div class="cad-mark cad-mark-br" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.2">
                    <path d="M22 14v8h-8"/>
                    <circle cx="22" cy="22" r="1.2" fill="#94a3b8"/>
                </svg>
            </div>

            <!-- Background Engineering Schematic Motifs (Subtle Precision Geometry) -->
            <div class="geom-motif geom-motif-tr" aria-hidden="true">
                <svg width="260" height="260" viewBox="0 0 260 260" fill="none" stroke="#0c2646" stroke-width="1" opacity="0.07">
                    <polygon points="130,20 230,75 130,130 30,75"/>
                    <polygon points="30,75 30,185 130,240 130,130"/>
                    <polygon points="230,75 230,185 130,240 130,130"/>
                    <line x1="130" y1="20" x2="130" y2="240" stroke-dasharray="3 3"/>
                    <line x1="30" y1="75" x2="230" y2="185" stroke-dasharray="3 3"/>
                    <circle cx="130" cy="130" r="70" stroke-dasharray="2 4"/>
                    <circle cx="130" cy="130" r="110" stroke-dasharray="1 5"/>
                </svg>
            </div>

            <div class="geom-motif geom-motif-bl" aria-hidden="true">
                <svg width="280" height="280" viewBox="0 0 280 280" fill="none" stroke="#0c2646" stroke-width="1" opacity="0.06">
                    <circle cx="140" cy="140" r="120" stroke-dasharray="4 6"/>
                    <circle cx="140" cy="140" r="80"/>
                    <circle cx="140" cy="140" r="40" stroke-dasharray="2 4"/>
                    <line x1="10" y1="140" x2="270" y2="140" stroke-dasharray="4 4"/>
                    <line x1="140" y1="10" x2="140" y2="270" stroke-dasharray="4 4"/>
                    <line x1="45" y1="45" x2="235" y2="235" stroke-dasharray="2 4"/>
                    <line x1="235" y1="45" x2="45" y2="235" stroke-dasharray="2 4"/>
                </svg>
            </div>

            <div class="geom-scale-right" aria-hidden="true">
                <svg width="16" height="240" viewBox="0 0 16 240" fill="none" stroke="#94a3b8" stroke-width="1" opacity="0.4">
                    <line x1="12" y1="0" x2="12" y2="240"/>
                    <line x1="4" y1="0" x2="12" y2="0"/>
                    <line x1="7" y1="24" x2="12" y2="24"/>
                    <line x1="7" y1="48" x2="12" y2="48"/>
                    <line x1="7" y1="72" x2="12" y2="72"/>
                    <line x1="4" y1="96" x2="12" y2="96"/>
                    <line x1="7" y1="120" x2="12" y2="120"/>
                    <line x1="7" y1="144" x2="12" y2="144"/>
                    <line x1="7" y1="168" x2="12" y2="168"/>
                    <line x1="4" y1="192" x2="12" y2="192"/>
                    <line x1="7" y1="216" x2="12" y2="216"/>
                    <line x1="4" y1="240" x2="12" y2="240"/>
                </svg>
            </div>

            <!-- Center Stage: Authentication Form -->
            <div class="interaction-center-stage">
                <h2 class="interaction-title">Sign in</h2>
                <p class="interaction-subtitle" id="auth-subtitle">
                    {{ $step === 'verify' ? 'Enter the 6-digit access code dispatched to your corporate inbox.' : 'Enter your registered corporate email to receive a secure passwordless access code.' }}
                </p>

                <!-- Unified Authentication Form: Email remains directly in its field at all times -->
                <form id="auth-unified-form" action="{{ route('auth.otp.send') }}" method="POST">
                    @csrf

                    <!-- Email Field: Always stays in place, no appearance change -->
                    <div class="form-field-group">
                        <label class="field-label" for="email">Corporate Email Address</label>
                        <div class="input-wrapper-relative">
                            <span class="input-icon-left">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </span>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="input-styled-text" 
                                value="{{ old('email', $email) }}" 
                                placeholder="name@bimabergawa.com" 
                                required 
                                autocomplete="email"
                                autofocus
                            >
                        </div>
                    </div>

                    <!-- 6-Digit OTP Field: Smoothly expands directly below email input -->
                    <div id="otp-section" class="auth-transition-stage" style="{{ $step === 'verify' ? '' : 'display: none;' }}">
                        <div class="form-field-group" style="margin-top: 20px;">
                            <label class="field-label">6-Digit Security Code</label>
                            
                            <!-- 6 Individual Digit Boxes with Auto-Advance & Paste -->
                            <div class="otp-inputs-grid" id="otp-grid">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5" autocomplete="off">
                            </div>
                        </div>

                        <!-- Resend OTP Button with Realtime Cooldown -->
                        <button type="button" class="btn-resend-clean" id="btn-resend" @if($cooldownSeconds > 0) disabled @endif>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                            </svg>
                            <span>Resend Security Code <strong id="cooldown-timer">@if($cooldownSeconds > 0)({{ $cooldownSeconds }}s)@endif</strong></span>
                        </button>
                    </div>

                    <!-- Circular Submit Action Button -->
                    <div class="submit-action-row">
                        <button type="submit" class="btn-circle-submit" id="btn-auth-submit" title="Continue" aria-label="Continue">
                            <span class="btn-arrow-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                    <polyline points="12 5 19 12 12 19"></polyline>
                                </svg>
                            </span>
                            <span class="btn-spinner-icon" style="display: none;">
                                <svg class="spin-animation" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-opacity="0.25"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-linecap="round"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </main>

    </div>

    <!-- High-End Theme-Matched Toast Notifications Container (Bottom-Right) -->
    <div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true"></div>

    <!-- Authentication Flow & Toast Script -->
    <script>
        (function() {
            // CSRF Token Setup
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            // Corporate Deep Navy Toast Notification Helper (Matches Brand #0c2646)
            window.showToast = function(message, type = 'info', title = '') {
                const container = document.getElementById('toast-container');
                if (!container || !message) return;

                const toast = document.createElement('div');
                toast.className = 'toast-item toast-' + type;
                toast.setAttribute('role', 'alert');

                let defaultTitle = '';
                if (type === 'success') {
                    defaultTitle = title || 'Success';
                } else if (type === 'error') {
                    defaultTitle = title || 'Notice';
                } else {
                    defaultTitle = title || 'Information';
                }

                toast.innerHTML = `
                    <div class="toast-content">
                        <div class="toast-title">${defaultTitle}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button type="button" class="toast-close-btn" aria-label="Dismiss notification">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                `;

                container.appendChild(toast);

                function removeToast() {
                    toast.classList.add('toast-closing');
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 250);
                }

                const closeBtn = toast.querySelector('.toast-close-btn');
                if (closeBtn) {
                    closeBtn.addEventListener('click', removeToast);
                }

                setTimeout(removeToast, 4500);
            };

            // Initial Flash Notifications from Server
            @if(session('status'))
                showToast(@json(session('status')), 'success');
            @endif

            @if($errors->any())
                @foreach($errors->all() as $error)
                    showToast(@json($error), 'error');
                @endforeach
            @endif

            // Elements
            const authForm = document.getElementById('auth-unified-form');
            const emailInput = document.getElementById('email');
            const authSubtitle = document.getElementById('auth-subtitle');
            const otpSection = document.getElementById('otp-section');
            const otpGrid = document.getElementById('otp-grid');
            const otpBoxes = document.querySelectorAll('.otp-digit-box');
            const btnSubmit = document.getElementById('btn-auth-submit');
            const btnResend = document.getElementById('btn-resend');
            const cooldownTimer = document.getElementById('cooldown-timer');
            
            let isOtpVisible = {{ $step === 'verify' ? 'true' : 'false' }};
            let cooldownInterval = null;

            function setBtnLoading(isLoading) {
                if (!btnSubmit) return;
                const arrow = btnSubmit.querySelector('.btn-arrow-icon');
                const spinner = btnSubmit.querySelector('.btn-spinner-icon');
                if (isLoading) {
                    btnSubmit.disabled = true;
                    if (arrow) arrow.style.display = 'none';
                    if (spinner) spinner.style.display = 'inline-flex';
                } else {
                    btnSubmit.disabled = false;
                    if (arrow) arrow.style.display = 'inline-flex';
                    if (spinner) spinner.style.display = 'none';
                }
            }

            function startCooldown(seconds) {
                if (cooldownInterval) clearInterval(cooldownInterval);
                let remaining = seconds;
                if (!btnResend || !cooldownTimer) return;

                btnResend.disabled = true;
                cooldownTimer.innerText = '(' + remaining + 's)';

                cooldownInterval = setInterval(function() {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(cooldownInterval);
                        btnResend.disabled = false;
                        cooldownTimer.innerText = '';
                    } else {
                        cooldownTimer.innerText = '(' + remaining + 's)';
                    }
                }, 1000);
            }

            @if($cooldownSeconds > 0)
                startCooldown({{ $cooldownSeconds }});
            @endif

            function getOtpCode() {
                let code = '';
                otpBoxes.forEach(box => code += box.value.trim());
                return code;
            }

            // Reveal 6-digit OTP fields in-place
            function revealOtpSection(cooldownSec) {
                if (otpSection) {
                    otpSection.style.display = 'block';
                    otpSection.classList.remove('auth-transition-stage');
                    void otpSection.offsetWidth; // trigger reflow
                    otpSection.classList.add('auth-transition-stage');
                }
                isOtpVisible = true;
                if (authSubtitle) {
                    authSubtitle.innerText = 'Enter the 6-digit access code dispatched to your corporate inbox.';
                }
                otpBoxes.forEach(box => box.value = '');
                if (otpBoxes.length > 0) otpBoxes[0].focus();
                startCooldown(cooldownSec || 60);
            }

            // AJAX: Request access code
            async function requestAccessCode() {
                const email = emailInput ? emailInput.value.trim() : '';
                if (!email) {
                    showToast('Please enter your corporate email address.', 'error');
                    if (emailInput) emailInput.focus();
                    return;
                }

                setBtnLoading(true);

                try {
                    const response = await fetch("{{ route('auth.otp.send') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ email: email })
                    });

                    const data = await response.json();

                    if (response.ok) {
                        showToast(data.message || 'Security code dispatched to your inbox.', 'success');
                        revealOtpSection(data.cooldown_seconds || 60);
                    } else {
                        const errMessage = data.message || (data.errors && data.errors.email ? data.errors.email[0] : 'Unable to send security code.');
                        showToast(errMessage, 'error');
                    }
                } catch (err) {
                    showToast('Network error encountered. Please check your connection.', 'error');
                } finally {
                    setBtnLoading(false);
                }
            }

            // AJAX: Verify 6-digit access code
            async function verifyAccessCode() {
                const email = emailInput ? emailInput.value.trim() : '';
                const code = getOtpCode();

                if (!email) {
                    showToast('Please enter your corporate email address.', 'error');
                    if (emailInput) emailInput.focus();
                    return;
                }

                if (code.length !== 6) {
                    showToast('Please enter all 6 digits of your security code.', 'error');
                    return;
                }

                setBtnLoading(true);

                try {
                    const response = await fetch("{{ route('auth.otp.verify') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ email: email, otp: code })
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'verified') {
                        showToast('Access verified. Redirecting to workspace...', 'success');
                        setTimeout(function() {
                            window.location.href = data.redirect || '{{ route("filament.admin.pages.dashboard") }}';
                        }, 500);
                    } else {
                        const errMessage = data.message || 'Invalid or expired security code.';
                        showToast(errMessage, 'error');
                        if (otpGrid) {
                            otpGrid.classList.add('shake');
                            setTimeout(() => otpGrid.classList.remove('shake'), 450);
                        }
                        otpBoxes.forEach(b => b.value = '');
                        if (otpBoxes.length > 0) otpBoxes[0].focus();
                        setBtnLoading(false);
                    }
                } catch (err) {
                    showToast('Verification failed due to a network error.', 'error');
                    setBtnLoading(false);
                }
            }

            // Form Submit: Decide whether to request OTP or verify OTP
            if (authForm) {
                authForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (!isOtpVisible) {
                        requestAccessCode();
                    } else {
                        const code = getOtpCode();
                        if (code.length === 6) {
                            verifyAccessCode();
                        } else if (code.length === 0) {
                            // If code is empty, re-request code for current email
                            requestAccessCode();
                        } else {
                            showToast('Please enter all 6 digits of your security code.', 'error');
                        }
                    }
                });
            }

            // OTP Digit Boxes: Auto navigation & auto submit on 6th digit
            otpBoxes.forEach((box, idx) => {
                box.addEventListener('input', (e) => {
                    const val = e.target.value.replace(/[^0-9]/g, '');
                    e.target.value = val ? val[val.length - 1] : '';

                    if (e.target.value && idx < otpBoxes.length - 1) {
                        otpBoxes[idx + 1].focus();
                    }

                    const code = getOtpCode();
                    if (code.length === 6) {
                        verifyAccessCode();
                    }
                });

                box.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !box.value && idx > 0) {
                        otpBoxes[idx - 1].focus();
                    }
                });

                box.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim().replace(/[^0-9]/g, '');
                    if (pasteData.length > 0) {
                        for (let i = 0; i < otpBoxes.length; i++) {
                            otpBoxes[i].value = pasteData[i] || '';
                        }
                        const code = getOtpCode();
                        if (code.length === 6) {
                            verifyAccessCode();
                        } else if (pasteData.length < otpBoxes.length) {
                            otpBoxes[pasteData.length].focus();
                        }
                    }
                });
            });

            // Resend Button Handler
            if (btnResend) {
                btnResend.addEventListener('click', async function(e) {
                    e.preventDefault();
                    if (btnResend.disabled) return;

                    const email = emailInput ? emailInput.value.trim() : '';
                    if (!email) {
                        showToast('Please enter your corporate email address.', 'error');
                        return;
                    }

                    btnResend.disabled = true;

                    try {
                        const response = await fetch("{{ route('auth.otp.send') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ email: email })
                        });

                        const data = await response.json();

                        if (response.ok) {
                            showToast(data.message || 'A new security code has been dispatched.', 'success');
                            startCooldown(data.cooldown_seconds || 60);
                            otpBoxes.forEach(b => b.value = '');
                            if (otpBoxes.length > 0) otpBoxes[0].focus();
                        } else {
                            const errMessage = data.message || 'Unable to resend code.';
                            showToast(errMessage, 'error');
                            btnResend.disabled = false;
                        }
                    } catch (err) {
                        showToast('Network error while requesting new code.', 'error');
                        btnResend.disabled = false;
                    }
                });
            }
        })();
    </script>

    <!-- Looping Typewriter Effect Script -->
    <script>
        (function() {
            const phrases = [
                "Panca Bima Bergawa Enterprise Resource Planning.",
                "Empowering industrial excellence with integrity and speed.",
                "Precision supply chain and reliable industrial procurement.",
                "Unrelenting dedication to operational efficiency and accuracy.",
                "Strategic partnerships powering Indonesian industrial growth.",
                "Integrated commercial governance and real-time HPP integrity."
            ];
            const target = document.getElementById('typewriter-target');
            if (!target) return;

            let phraseIndex = 0;
            let charIndex = 0;
            let isDeleting = false;
            const typingSpeed = 38;
            const deletingSpeed = 18;
            const pauseEnd = 2400;
            const pauseStart = 350;

            function tick() {
                const currentPhrase = phrases[phraseIndex];

                if (isDeleting) {
                    target.textContent = currentPhrase.substring(0, charIndex - 1);
                    charIndex--;
                } else {
                    target.textContent = currentPhrase.substring(0, charIndex + 1);
                    charIndex++;
                }

                let delay = isDeleting ? deletingSpeed : typingSpeed;

                if (!isDeleting && charIndex === currentPhrase.length) {
                    delay = pauseEnd;
                    isDeleting = true;
                } else if (isDeleting && charIndex === 0) {
                    isDeleting = false;
                    phraseIndex = (phraseIndex + 1) % phrases.length;
                    delay = pauseStart;
                }

                setTimeout(tick, delay);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(tick, 300);
                });
            } else {
                setTimeout(tick, 300);
            }
        })();
    </script>
</body>
</html>
