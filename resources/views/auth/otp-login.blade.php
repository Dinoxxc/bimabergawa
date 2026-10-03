<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem ERP - PT. Panca Bima Bergawa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        *, ::before, ::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f4f4f5;
            color: #18181b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background-color: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        .brand-header {
            margin-bottom: 28px;
            text-align: center;
        }
        .company-name {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #09090b;
            text-transform: uppercase;
        }
        .system-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            color: #71717a;
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
            padding: 3px 8px;
            border-radius: 4px;
            margin-top: 8px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .form-title {
            font-family: 'Outfit', sans-serif;
            font-size: 17px;
            font-weight: 700;
            color: #09090b;
            margin-bottom: 8px;
        }
        .form-desc {
            font-size: 13px;
            color: #71717a;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #18181b;
            margin-bottom: 6px;
        }
        .input-text {
            width: 100%;
            height: 42px;
            padding: 0 14px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #09090b;
            background-color: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .input-text:focus {
            border-color: #27272a;
        }
        .input-otp {
            width: 100%;
            height: 52px;
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 12px;
            text-align: center;
            color: #09090b;
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            outline: none;
        }
        .input-otp:focus {
            border-color: #27272a;
            background-color: #ffffff;
        }
        .btn-primary {
            width: 100%;
            height: 42px;
            background-color: #18181b;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.15s ease;
        }
        .btn-primary:hover {
            background-color: #27272a;
        }
        .btn-secondary {
            background: none;
            border: none;
            font-size: 13px;
            color: #71717a;
            cursor: pointer;
            text-decoration: underline;
            padding: 8px 0;
            margin-top: 12px;
            width: 100%;
            text-align: center;
            display: block;
        }
        .btn-secondary:hover {
            color: #09090b;
        }
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fee2e2;
            color: #991b1b;
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .alert-success {
            background-color: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #166534;
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .cooldown-text {
            font-size: 12px;
            color: #71717a;
            text-align: center;
            margin-top: 10px;
        }
        .footer-note {
            margin-top: 28px;
            border-top: 1px solid #f4f4f5;
            padding-top: 16px;
            font-size: 11px;
            color: #a1a1aa;
            text-align: center;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-header">
            <h1 class="company-name">PT. Panca Bima Bergawa</h1>
            <div class="system-badge">Commercial ERP &bull; Rasuna Said</div>
        </div>

        @if(session('status'))
            <div class="alert-success">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if($step === 'verify')
            <!-- Step 2: Input 6-Digit OTP -->
            <div class="form-title">Verifikasi Kode Masuk</div>
            <div class="form-desc">
                Masukkan 6-digit kode OTP yang telah dikirimkan ke <strong>{{ $email }}</strong>. Masa aktif kode 5 menit.
            </div>

            <form action="{{ route('auth.otp.verify') }}" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="form-group">
                    <label class="label" for="otp">Kode Verifikasi (6-Digit OTP)</label>
                    <input type="text" id="otp" name="otp" class="input-otp" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required autofocus placeholder="000000">
                </div>

                <button type="submit" class="btn-primary">Verifikasi & Masuk</button>
            </form>

            <form action="{{ route('auth.otp.send') }}" method="POST" id="resend-form">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">
                <button type="submit" class="btn-secondary" id="btn-resend" @if($cooldownSeconds > 0) disabled @endif>
                    Kirim Ulang Kode OTP <span id="cooldown-timer">@if($cooldownSeconds > 0)({{ $cooldownSeconds }}s)@endif</span>
                </button>
            </form>

            <a href="{{ route('auth.otp.login') }}" class="btn-secondary" style="font-size: 12px; margin-top: 4px;">Ganti Alamat Email</a>

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

        @else
            <!-- Step 1: Input Email -->
            <div class="form-title">Masuk ke Panel ERP</div>
            <div class="form-desc">
                Masukkan alamat email resmi terdaftar Anda. Sistem akan mengirimkan kode OTP satu kali pakai tanpa kata sandi statis.
            </div>

            <form action="{{ route('auth.otp.send') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label class="label" for="email">Alamat Email Terdaftar</label>
                    <input type="email" id="email" name="email" class="input-text" value="{{ old('email', $email) }}" placeholder="nama@bimabergawa.com" required autofocus>
                </div>

                <button type="submit" class="btn-primary">Kirim Kode OTP</button>
            </form>
        @endif

        <div class="footer-note">
            Dilindungi oleh Otentikasi Passwordless CSPRNG SHA-256.<br>
            Akses khusus staf internal dan mitra agen terdaftar.
        </div>
    </div>
</body>
</html>
