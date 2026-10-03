<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem ERP - PT. Panca Bima Bergawa</title>
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
            background-color: var(--bg-main);
            color: #ffffff;
            padding: 56px 64px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            background-image: 
                radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(9, 9, 11, 0.7), rgba(9, 9, 11, 0.98));
            background-size: 28px 28px, 100% 100%;
            border-right: 1px solid #18181b;
            overflow: hidden;
        }

        .stage-showcase::before {
            content: '';
            position: absolute;
            top: -150px;
            left: -150px;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.03) 0%, transparent 70%);
            pointer-events: none;
        }

        .brand-emblem-box {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .emblem-svg-wrap {
            width: 44px;
            height: 44px;
            background-color: #18181b;
            border: 1px solid #27272a;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-meta-title {
            font-family: 'Outfit', sans-serif;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #ffffff;
            text-transform: uppercase;
        }

        .brand-meta-sub {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 600;
            color: #a1a1aa;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .showcase-center {
            margin: 40px 0;
            max-width: 560px;
        }

        .system-category-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 14px;
            background-color: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 9999px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 24px;
        }

        .showcase-headline {
            font-family: 'Outfit', sans-serif;
            font-size: 46px;
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -0.035em;
            color: #ffffff;
            margin-bottom: 20px;
        }

        .showcase-subtext {
            font-size: 15px;
            line-height: 1.65;
            color: #a1a1aa;
            margin-bottom: 32px;
        }

        .discreet-notice-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background-color: rgba(24, 24, 27, 0.5);
            border: 1px solid rgba(39, 39, 42, 0.7);
            border-radius: 8px;
            padding: 16px 20px;
            backdrop-filter: blur(8px);
        }

        .notice-svg-icon {
            color: #71717a;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .notice-heading {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: #e4e4e7;
            margin-bottom: 4px;
        }

        .notice-body {
            font-size: 13px;
            line-height: 1.5;
            color: #71717a;
        }

        .showcase-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #52525b;
            border-top: 1px solid #18181b;
            padding-top: 24px;
        }

        /* 2. Right Stage - Interaction Canvas */
        .stage-interaction {
            flex: 1;
            background-color: var(--bg-canvas);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 56px 64px;
            position: relative;
        }

        .form-stage-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .security-badge-header {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: var(--bg-subtle);
            border: 1px solid var(--border-light);
            padding: 5px 12px;
            border-radius: 6px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-heading);
            letter-spacing: 0.04em;
            margin-bottom: 24px;
        }

        .interaction-title {
            font-family: 'Outfit', sans-serif;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-heading);
            margin-bottom: 8px;
        }

        .interaction-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 28px;
        }

        .alert-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.45;
            margin-bottom: 24px;
        }

        .alert-box.success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
        }

        .alert-box.error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .form-field-group {
            margin-bottom: 22px;
        }

        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-heading);
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
            color: #71717a;
            display: flex;
            align-items: center;
            pointer-events: none;
        }

        .input-styled-text {
            width: 100%;
            height: 48px;
            padding: 0 14px 0 44px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: var(--text-heading);
            background-color: #ffffff;
            border: 1.5px solid var(--border-light);
            border-radius: 8px;
            outline: none;
            transition: all 0.15s ease;
        }

        .input-styled-text:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(9, 9, 11, 0.06);
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
            color: var(--text-heading);
            background-color: var(--bg-subtle);
            border: 1.5px solid var(--border-light);
            border-radius: 8px;
            outline: none;
            transition: all 0.15s ease;
        }

        .otp-digit-box:focus {
            border-color: var(--border-focus);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(9, 9, 11, 0.08);
            transform: translateY(-1px);
        }

        /* Buttons */
        .btn-black-cta {
            width: 100%;
            height: 48px;
            background-color: #09090b;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #09090b;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .btn-black-cta:hover {
            background-color: #27272a;
            border-color: #27272a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
        }

        .btn-black-cta:active {
            transform: translateY(0);
        }

        .btn-resend-clean {
            background: none;
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            transition: color 0.15s ease;
            margin-top: 14px;
        }

        .btn-resend-clean:hover:not(:disabled) {
            color: var(--text-heading);
            text-decoration: underline;
        }

        .btn-resend-clean:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        .change-email-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #71717a;
            text-decoration: none;
            margin-top: 10px;
            transition: color 0.15s ease;
        }

        .change-email-link:hover {
            color: #09090b;
        }

        .security-footer-compact {
            margin-top: 28px;
            text-align: center;
            font-size: 11px;
            color: #a1a1aa;
            line-height: 1.5;
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
            }
            .showcase-headline {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="split-layout">
        
        <!-- LEFT: Technical & Industrial Corporate Showcase -->
        <section class="stage-showcase">
            <div>
                <!-- Brand Header -->
                <div class="brand-emblem-box">
                    <div class="emblem-svg-wrap">
                        <!-- Pure SVG Geometric Monogram Logo -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 17L12 22L22 17" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12L12 17L22 12" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div>
                        <div class="brand-meta-title">PT. Panca Bima Bergawa</div>
                        <div class="brand-meta-sub">Internal System Portal</div>
                    </div>
                </div>

                <!-- Showcase Main Pitch -->
                <div class="showcase-center">
                    <div class="system-category-pill">
                        <span>PORTAL AKSES INTERNAL</span>
                    </div>

                    <h1 class="showcase-headline">
                        Panca Bima Bergawa Enterprise Resource Planning.
                    </h1>
                    <p class="showcase-subtext">
                        Sistem tata kelola operasional terpadu dan manajemen proses internal PT. Panca Bima Bergawa. Akses ke dalam platform ini dibatasi secara ketat hanya bagi pengguna yang memiliki otorisasi resmi perusahaan.
                    </p>

                    <div class="discreet-notice-card">
                        <div class="notice-svg-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="notice-heading">Akses Khusus Terotorisasi</div>
                            <div class="notice-body">Seluruh aktivitas dan riwayat akses dalam lingkungan sistem ini dicatat dan diawasi oleh administrator keamanan perusahaan.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Showcase Footer -->
            <footer class="showcase-footer">
                <div>&copy; 2026 PT. Panca Bima Bergawa. Seluruh hak cipta dilindungi.</div>
                <div style="font-family: 'JetBrains Mono', monospace;">Versi v1.5.0-ERP</div>
            </footer>
        </section>


        <!-- RIGHT: Modern High-Precision Authentication Canvas -->
        <main class="stage-interaction">
            <div class="form-stage-wrapper">
                
                <!-- Security Header Badge -->
                <div class="security-badge-header">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>PBB-ERP // SECURE ACCESS PORTAL</span>
                </div>

                <!-- Notifications -->
                @if(session('status'))
                    <div class="alert-box success">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 1px;">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <div>{{ session('status') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-box error">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 1px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <div>
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif


                @if($step === 'verify')
                    <!-- ============================================== -->
                    <!-- STEP 2: 6-DIGIT OTP VERIFICATION INTERFACE     -->
                    <!-- ============================================== -->
                    <div class="interaction-title">Verifikasi Kode OTP</div>
                    <div class="interaction-subtitle">
                        Kode 6-digit CSPRNG telah dikirim ke <strong>{{ $email }}</strong>. Masukkan kode berikut (berlaku 5 menit).
                    </div>

                    <form action="{{ route('auth.otp.verify') }}" method="POST" id="verify-otp-form">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <input type="hidden" name="otp" id="otp-complete-value" value="">

                        <div class="form-field-group">
                            <label class="field-label">Kode Verifikasi 6-Digit</label>
                            
                            <!-- 6 Individual Digit Boxes with Auto-Advance & Paste -->
                            <div class="otp-inputs-grid" id="otp-grid">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" autofocus data-index="0" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4" autocomplete="off">
                                <input type="text" class="otp-digit-box" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5" autocomplete="off">
                            </div>
                        </div>

                        <button type="submit" class="btn-black-cta" id="btn-submit-otp">
                            <span>Verifikasi & Masuk ke Panel</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </form>

                    <!-- Resend OTP Form with Realtime Cooldown -->
                    <form action="{{ route('auth.otp.send') }}" method="POST" id="resend-form">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button type="submit" class="btn-resend-clean" id="btn-resend" @if($cooldownSeconds > 0) disabled @endif>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
                            </svg>
                            <span>Kirim Ulang Kode OTP <strong id="cooldown-timer">@if($cooldownSeconds > 0)({{ $cooldownSeconds }}s)@endif</strong></span>
                        </button>
                    </form>

                    <!-- Change Email Link -->
                    <a href="{{ route('auth.otp.login') }}" class="change-email-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <span>Ganti Alamat Email</span>
                    </a>

                    <!-- Cooldown Countdown Script -->
                    @if($cooldownSeconds > 0)
                        <script>
                            (function() {
                                let remaining = {{ $cooldownSeconds }};
                                const btn = document.getElementById('btn-resend');
                                const timer = document.getElementById('cooldown-timer');
                                const interval = setInterval(function() {
                                    remaining--;
                                    if (remaining <= 0) {
                                        clearInterval(interval);
                                        btn.removeAttribute('disabled');
                                        timer.innerText = '';
                                    } else {
                                        timer.innerText = '(' + remaining + 's)';
                                    }
                                }, 1000);
                            })();
                        </script>
                    @endif

                    <!-- OTP Inputs Navigation & Paste Script -->
                    <script>
                        (function() {
                            const boxes = document.querySelectorAll('.otp-digit-box');
                            const hiddenInput = document.getElementById('otp-complete-value');
                            const form = document.getElementById('verify-otp-form');

                            function syncHiddenValue() {
                                let val = '';
                                boxes.forEach(b => val += b.value);
                                hiddenInput.value = val;
                                return val;
                            }

                            boxes.forEach((box, idx) => {
                                box.addEventListener('input', (e) => {
                                    const val = e.target.value.replace(/[^0-9]/g, '');
                                    e.target.value = val ? val[val.length - 1] : '';
                                    
                                    if (e.target.value && idx < boxes.length - 1) {
                                        boxes[idx + 1].focus();
                                    }

                                    const fullVal = syncHiddenValue();
                                    if (fullVal.length === 6) {
                                        form.submit();
                                    }
                                });

                                box.addEventListener('keydown', (e) => {
                                    if (e.key === 'Backspace' && !box.value && idx > 0) {
                                        boxes[idx - 1].focus();
                                    }
                                });

                                box.addEventListener('paste', (e) => {
                                    e.preventDefault();
                                    const pasteData = (e.clipboardData || window.clipboardData).getData('text').trim().replace(/[^0-9]/g, '');
                                    if (pasteData.length > 0) {
                                        for (let i = 0; i < boxes.length; i++) {
                                            boxes[i].value = pasteData[i] || '';
                                        }
                                        const fullVal = syncHiddenValue();
                                        if (fullVal.length === 6) {
                                            form.submit();
                                        } else if (pasteData.length < boxes.length) {
                                            boxes[pasteData.length].focus();
                                        }
                                    }
                                });
                            });

                            form.addEventListener('submit', (e) => {
                                syncHiddenValue();
                            });
                        })();
                    </script>


                @else
                    <!-- ============================================== -->
                    <!-- STEP 1: EMAIL ADDRESS INPUT INTERFACE          -->
                    <!-- ============================================== -->
                    <div class="interaction-title">Masuk ke Panel ERP</div>
                    <div class="interaction-subtitle">
                        Masukkan email terdaftar Anda untuk menerima token otentikasi tanpa sandi statis.
                    </div>

                    <form action="{{ route('auth.otp.send') }}" method="POST">
                        @csrf
                        
                        <div class="form-field-group">
                            <label class="field-label" for="email">Alamat Email Terdaftar</label>
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
                                    placeholder="nama@bimabergawa.com" 
                                    required 
                                    autofocus
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn-black-cta">
                            <span>Kirim Kode OTP Masuk</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </button>
                    </form>

                @endif

                <!-- Security Note Footnote -->
                <div class="security-footer-compact">
                    Dilindungi oleh Sistem Otentikasi Terpadu.<br>
                    Akses terbatas bagi personel terotorisasi PT. Panca Bima Bergawa.
                </div>

            </div>
        </main>

    </div>
</body>
</html>
