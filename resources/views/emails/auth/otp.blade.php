<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi OTP - PT. Panca Bima Bergawa</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f5;
            color: #18181b;
            margin: 0;
            padding: 24px;
            line-height: 1.5;
        }
        .container {
            max-width: 540px;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 32px;
        }
        .header {
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .company-title {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #09090b;
            text-transform: uppercase;
            margin: 0;
        }
        .sub-title {
            font-size: 12px;
            color: #71717a;
            margin-top: 4px;
        }
        .content {
            margin-bottom: 28px;
        }
        .otp-box {
            background-color: #fafafa;
            border: 1px dashed #27272a;
            border-radius: 4px;
            text-align: center;
            padding: 20px;
            margin: 24px 0;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 10px;
            color: #09090b;
        }
        .warning-box {
            font-size: 12px;
            color: #71717a;
            border-left: 3px solid #71717a;
            padding-left: 12px;
            margin-top: 20px;
        }
        .footer {
            border-top: 1px solid #e4e4e7;
            padding-top: 20px;
            font-size: 11px;
            color: #a1a1aa;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="company-title">PT. Panca Bima Bergawa</h1>
            <div class="sub-title">Sistem Otentikasi Terpadu Internal Commercial ERP</div>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $user->name }}</strong>,</p>
            <p>Berikut adalah 6-digit kode verifikasi (OTP) untuk masuk ke panel internal ERP PT. Panca Bima Bergawa:</p>
            
            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="warning-box">
                <p><strong>Perhatian Keamanan:</strong></p>
                <p>Kode ini hanya berlaku selama <strong>{{ $validityMinutes }} menit</strong> (300 detik). Jangan pernah memberikan kode ini kepada pihak mana pun, termasuk staf yang mengatasnamakan manajemen atau tim IT PT. Panca Bima Bergawa.</p>
                <p>Jika Anda tidak merasa melakukan permintaan masuk ini, harap segera hubungi tim Security IT atau ubah status akun Anda.</p>
            </div>
        </div>
        <div class="footer">
            <p>PT. Panca Bima Bergawa - One-Stop Industrial General Supplier & Procurement Partner</p>
            <p>Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920</p>
            <p>Pesan otomatis dikirim oleh server keamanan ERP. Jangan membalas email ini.</p>
        </div>
    </div>
</body>
</html>
