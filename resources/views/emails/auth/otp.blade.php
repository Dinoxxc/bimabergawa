<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Access Code - PT. Panca Bima Bergawa</title>
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
            <div class="sub-title">Unified Authentication System &bull; Internal Commercial ERP</div>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $user->name }}</strong>,</p>
            <p>Here is your 6-digit security access code (OTP) to sign in to the PT. Panca Bima Bergawa Internal ERP Portal:</p>
            
            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <div class="warning-box">
                <p><strong>Security Notice:</strong></p>
                <p>This code expires in <strong>{{ $validityMinutes }} minutes</strong> (300 seconds). Never disclose this code to anyone, including personnel claiming to represent PT. Panca Bima Bergawa management or IT support.</p>
                <p>If you did not initiate this sign-in request, please notify your IT Security team immediately.</p>
            </div>
        </div>
        <div class="footer">
            <p>PT. Panca Bima Bergawa - One-Stop Industrial General Supplier & Procurement Partner</p>
            <p>Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920</p>
            <p>Automated message dispatched by ERP Security Server. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
