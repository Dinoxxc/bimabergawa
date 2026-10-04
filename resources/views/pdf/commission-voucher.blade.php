<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Commission Voucher - {{ $payout->payout_number ?? 'COM-VOUCHER' }}</title>
    <style>
        @page {
            margin: 28px 36px 36px 36px;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #18181b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #09090b;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .company-title {
            font-size: 14pt;
            font-weight: bold;
            color: #09090b;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .company-subtitle {
            font-size: 8pt;
            color: #52525b;
            margin-top: 2px;
            line-height: 1.3;
        }
        .doc-title-block {
            text-align: right;
            vertical-align: top;
        }
        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            color: #09090b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-meta {
            font-size: 8.5pt;
            color: #3f3f46;
            margin-top: 4px;
        }
        .doc-meta strong {
            color: #09090b;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 0;
        }
        .info-card {
            width: 48%;
            border: 1px solid #e4e4e7;
            padding: 8px 10px;
            background-color: #fafafa;
            vertical-align: top;
        }
        .card-header {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #71717a;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .card-row {
            margin-bottom: 3px;
            font-size: 8.5pt;
        }
        .card-label {
            color: #71717a;
            display: inline-block;
            width: 100px;
        }
        .card-val {
            font-weight: 500;
            color: #09090b;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 14px;
        }
        .table-data th {
            background-color: #09090b;
            color: #ffffff;
            font-size: 8pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            border: 1px solid #09090b;
        }
        .table-data td {
            padding: 8px 8px;
            font-size: 8.5pt;
            border: 1px solid #e4e4e7;
            vertical-align: top;
        }
        .table-data tr:nth-child(even) {
            background-color: #fbfbfb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-mono {
            font-family: 'Courier New', Courier, monospace;
        }
        .summary-box {
            width: 100%;
            margin-top: 6px;
            margin-bottom: 16px;
        }
        .summary-table {
            float: right;
            width: 50%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 5px 8px;
            font-size: 8.5pt;
        }
        .summary-table .label {
            color: #52525b;
            text-align: right;
        }
        .summary-table .amount {
            font-weight: bold;
            text-align: right;
            border-bottom: 1px solid #e4e4e7;
        }
        .summary-table .grand-total {
            font-size: 10pt;
            font-weight: bold;
            color: #09090b;
            border-top: 2px solid #09090b;
            border-bottom: 2px solid #09090b;
            background-color: #f4f4f5;
        }
        .clear {
            clear: both;
        }
        .notes-card {
            border: 1px solid #e4e4e7;
            padding: 8px 10px;
            background-color: #fafafa;
            margin-bottom: 14px;
            font-size: 8pt;
            color: #3f3f46;
        }
        .signatures-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .sign-col {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .sign-title {
            font-size: 8pt;
            font-weight: bold;
            color: #71717a;
            text-transform: uppercase;
            margin-bottom: 55px;
        }
        .sign-name {
            font-size: 9pt;
            font-weight: bold;
            color: #09090b;
            border-top: 1px solid #09090b;
            display: inline-block;
            min-width: 170px;
            padding-top: 4px;
        }
        .sign-role {
            font-size: 7.5pt;
            color: #71717a;
            margin-top: 1px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="company-title">PT. PANCA BIMA BERGAWA</div>
                <div class="company-subtitle">
                    Industrial General Supplier & Procurement Partner<br>
                    Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920<br>
                    Tel: (021) 527-4180 | Email: commercial@bimabergawa.com | Web: www.bimabergawa.com
                </div>
            </td>
            <td class="doc-title-block" style="width: 45%;">
                <div class="doc-title">COMMISSION VOUCHER</div>
                <div class="doc-meta">
                    <strong>Voucher #:</strong> <span class="font-mono">{{ $payout->payout_number ?? 'PBB/COM/'.date('Y/m/').sprintf('%04d', $payout->id) }}</span><br>
                    <strong>Disbursement Date:</strong> {{ $payout->paid_at ? $payout->paid_at->format('d M Y') : date('d M Y') }}<br>
                    <strong>Status:</strong> [{{ strtoupper($payout->status) }}]
                </div>
            </td>
        </tr>
    </table>

    <!-- Parties Information -->
    <table class="info-grid" cellpadding="0" cellspacing="0">
        <tr>
            <td class="info-card">
                <div class="card-header">Partner Beneficiary</div>
                <div class="card-row">
                    <span class="card-label">Agent Name:</span>
                    <span class="card-val">{{ $payout->freelanceSales->name ?? 'Freelance Partner' }}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Partner Code:</span>
                    <span class="card-val font-mono">{{ $payout->freelanceSales->referral_code ?? '-' }}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Bank Destination:</span>
                    <span class="card-val">{{ $payout->freelanceSales->bank_name ?? '-' }}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Account Name:</span>
                    <span class="card-val">{{ $payout->freelanceSales->bank_account_name ?? '-' }}</span>
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td class="info-card">
                <div class="card-header">Transaction Reference</div>
                <div class="card-row">
                    <span class="card-label">Sales Order Ref:</span>
                    <span class="card-val font-mono">{{ $payout->salesOrder?->order_number ?? 'N/A (Multi-deal Payout)' }}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Client Entity:</span>
                    <span class="card-val">{{ $payout->salesOrder?->quotation?->inquiry?->company_name ?? 'Consolidated Incentive' }}</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Payment Channel:</span>
                    <span class="card-val">Corporate Electronic Bank Transfer</span>
                </div>
                <div class="card-row">
                    <span class="card-label">Disbursement By:</span>
                    <span class="card-val">PT. Panca Bima Bergawa - Treasury</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Ledger Details Table -->
    <table class="table-data" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 8%;" class="text-center">No</th>
                <th style="width: 42%;">Description & Project Context</th>
                <th style="width: 20%;" class="text-center">Reference Type</th>
                <th style="width: 30%;" class="text-right">Net Amount (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>
                    <strong>Commercial Deal Commission Disbursed</strong><br>
                    <span style="font-size: 8pt; color: #52525b;">
                        {{ $payout->notes ?? 'Settlement of approved commission payout ticket.' }}
                    </span>
                </td>
                <td class="text-center font-mono">
                    {{ strtoupper($payout->type) }}
                </td>
                <td class="text-right font-mono" style="font-size: 9.5pt; font-weight: bold;">
                    Rp {{ number_format((float) $payout->amount, 2, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Summary Box -->
    <div class="summary-box">
        <table class="summary-table" cellpadding="0" cellspacing="0">
            <tr>
                <td class="label">Gross Incentive:</td>
                <td class="amount font-mono">Rp {{ number_format((float) $payout->amount, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Withholding Tax (PPh 21 / Flat):</td>
                <td class="amount font-mono">Rp 0,00</td>
            </tr>
            <tr class="grand-total">
                <td class="label" style="font-size: 9pt;">TOTAL NET DISBURSEMENT:</td>
                <td class="amount font-mono" style="font-size: 10pt;">Rp {{ number_format((float) $payout->amount, 2, ',', '.') }}</td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

    <!-- Corporate Disclaimer Notes -->
    <div class="notes-card">
        <strong>Corporate Disbursement Notice:</strong><br>
        1. This commission disbursement voucher serves as official evidence of commercial incentive payment by PT. Panca Bima Bergawa.<br>
        2. Transfer verification has been completed by the Finance & Accounting Department and reconciled with official bank statements.<br>
        3. All personal partner data and banking information are strictly encrypted and processed under UU Pelindungan Data Pribadi (UU PDP No. 27/2022).
    </div>

    <!-- Signatures Section -->
    <table class="signatures-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="sign-col">
                <div class="sign-title">Acknowledged by Partner</div>
                <div class="sign-name">{{ $payout->freelanceSales->name ?? 'Freelance Partner' }}</div>
                <div class="sign-role">Commercial Freelance Partner</div>
            </td>
            <td class="sign-col">
                <div class="sign-title">Verified & Disbursed by</div>
                <div class="sign-name">Finance & Treasury Dept</div>
                <div class="sign-role">PT. Panca Bima Bergawa</div>
            </td>
        </tr>
    </table>

</body>
</html>
