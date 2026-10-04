<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation - {{ $quotation->quotation_number }}</title>
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
            border-collapse: collapse;
        }
        .info-box {
            width: 50%;
            vertical-align: top;
            padding: 8px 10px;
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
        }
        .info-box-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #71717a;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 3px;
        }
        .info-row {
            margin-bottom: 3px;
        }
        .info-label {
            display: inline-block;
            width: 90px;
            color: #71717a;
            font-size: 8pt;
        }
        .info-value {
            font-weight: 600;
            color: #18181b;
            font-size: 8.5pt;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 12px;
        }
        .items-table th {
            background-color: #18181b;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: 1px solid #18181b;
            text-align: left;
        }
        .items-table td {
            padding: 6px 8px;
            border: 1px solid #e4e4e7;
            font-size: 8pt;
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .summary-wrapper {
            width: 100%;
            margin-top: 6px;
            page-break-inside: avoid;
        }
        .terms-column {
            width: 58%;
            vertical-align: top;
            padding-right: 14px;
        }
        .summary-column {
            width: 42%;
            vertical-align: top;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 4px 8px;
            font-size: 8.5pt;
            border-bottom: 1px solid #f4f4f5;
        }
        .summary-table tr.total-row td {
            border-top: 2px solid #18181b;
            border-bottom: 2px solid #18181b;
            font-weight: bold;
            font-size: 10pt;
            background-color: #fafafa;
            color: #09090b;
        }
        .section-heading {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #09090b;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .terms-box {
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
            padding: 8px 10px;
            font-size: 7.5pt;
            color: #52525b;
            line-height: 1.45;
        }
        .terms-box ol {
            margin: 0;
            padding-left: 14px;
        }
        .terms-box li {
            margin-bottom: 2px;
        }
        .bank-info {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #d4d4d8;
            font-size: 7.5pt;
        }
        .signatures {
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 45%;
            vertical-align: top;
            text-align: center;
        }
        .signature-space {
            height: 60px;
        }
        .signature-name {
            font-weight: bold;
            font-size: 8.5pt;
            color: #09090b;
            border-top: 1px solid #71717a;
            padding-top: 4px;
            display: inline-block;
            min-width: 180px;
        }
        .signature-role {
            font-size: 7.5pt;
            color: #71717a;
        }
        .footer-note {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7pt;
            color: #a1a1aa;
            text-align: center;
            border-top: 1px solid #f4f4f5;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="company-title">PT. PANCA BIMA BERGAWA</div>
                <div class="company-subtitle">
                    Industrial Electrical, Mechanical, Automation & MRO Supply Partner<br>
                    Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920<br>
                    Email: sales@bimabergawa.com | Web: bimabergawa.com | Tel: +62 21 5290 1234
                </div>
            </td>
            <td class="doc-title-block">
                <div class="doc-title">QUOTATION</div>
                <div class="doc-meta">
                    <strong>No:</strong> {{ $quotation->quotation_number }}<br>
                    <strong>Date:</strong> {{ $quotation->created_at ? $quotation->created_at->format('d M Y') : now()->format('d M Y') }}<br>
                    <strong>Validity:</strong> {{ $quotation->validity_days ?? 14 }} Days
                    @if($quotation->expires_at)
                        (Until {{ $quotation->expires_at->format('d M Y') }})
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="info-grid" cellpadding="0" cellspacing="0">
        <tr>
            <td class="info-box" style="margin-right: 6px;">
                <div class="info-box-title">Client Details</div>
                <div class="info-row"><span class="info-label">Company:</span> <span class="info-value">{{ $quotation->company_name }}</span></div>
                <div class="info-row"><span class="info-label">Contact:</span> <span class="info-value">{{ $quotation->customer_name }}</span></div>
                @if($quotation->inquiry && $quotation->inquiry->phone_wa)
                    <div class="info-row"><span class="info-label">Phone / WA:</span> <span class="info-value">{{ $quotation->inquiry->phone_wa }}</span></div>
                @endif
                @if($quotation->inquiry && $quotation->inquiry->email)
                    <div class="info-row"><span class="info-label">Email:</span> <span class="info-value">{{ $quotation->inquiry->email }}</span></div>
                @endif
            </td>
            <td style="width: 12px;"></td>
            <td class="info-box">
                <div class="info-box-title">Commercial & Project Terms</div>
                <div class="info-row"><span class="info-label">Project:</span> <span class="info-value">{{ $quotation->project_name ?: 'General Supply Procurement' }}</span></div>
                <div class="info-row"><span class="info-label">Delivery:</span> <span class="info-value">{{ $quotation->delivery_terms ?: 'Loco Warehouse PBB Jakarta' }}</span></div>
                <div class="info-row"><span class="info-label">Currency:</span> <span class="info-value">{{ $quotation->currency ?: 'IDR' }}</span></div>
                @if($quotation->inquiry)
                    <div class="info-row"><span class="info-label">Inquiry Ref:</span> <span class="info-value">{{ $quotation->inquiry->inquiry_number }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="text-center" style="width: 25px;">No</th>
                <th style="width: 110px;">Part / Model #</th>
                <th>Description / Specification</th>
                <th class="text-center" style="width: 40px;">Qty</th>
                <th class="text-center" style="width: 35px;">UOM</th>
                <th class="text-right" style="width: 80px;">Unit Price ({{ $quotation->currency }})</th>
                <th class="text-center" style="width: 45px;">Disc</th>
                <th class="text-right" style="width: 95px;">Total ({{ $quotation->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotation->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-size: 7.5pt; font-weight: bold;">
                        {{ $item->model_number ?: ($item->product ? $item->product->model_number : '-') }}
                    </td>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if($item->lead_time)
                            <div style="font-size: 7pt; color: #52525b; margin-top: 1px;">Lead time: {{ $item->lead_time }}</div>
                        @endif
                        @if($item->warranty_period)
                            <div style="font-size: 7pt; color: #52525b;">Warranty: {{ $item->warranty_period }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format((float) $item->quantity, 0) }}</td>
                    <td class="text-center">{{ strtoupper($item->uom ?: 'PCS') }}</td>
                    <td class="text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="text-center">{{ $item->discount_percent > 0 ? number_format((float) $item->discount_percent, 1).'%' : '-' }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ number_format((float) $item->total_price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 12px; color: #71717a;">No line items in this quotation.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-wrapper" cellpadding="0" cellspacing="0">
        <tr>
            <td class="terms-column">
                <div class="section-heading">Terms & Conditions</div>
                <div class="terms-box">
                    <ol>
                        <li>Prices quoted are based on standard catalog specifications and delivery terms stated above.</li>
                        <li>Lead time begins upon receipt of official PO and confirmation of agreed payment terms.</li>
                        <li>Goods once delivered in good condition cannot be returned without prior written authorization.</li>
                        <li>Quotation is valid for {{ $quotation->validity_days ?? 14 }} calendar days from issuance date.</li>
                    </ol>
                    <div class="bank-info">
                        <strong>Official Payment Remittance (PT. Panca Bima Bergawa):</strong><br>
                        Bank Central Asia (BCA) KCU Rasuna Said | A/C: 546-088-9999 (IDR)<br>
                        Bank Mandiri KC Plaza Mandiri | A/C: 102-00-0988776-5 (IDR)
                    </div>
                </div>
            </td>
            <td class="summary-column">
                <table class="summary-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="color: #71717a;">Subtotal</td>
                        <td class="text-right" style="font-weight: 600;">{{ number_format((float) $quotation->subtotal, 2) }}</td>
                    </tr>
                    @if($quotation->discount_amount > 0)
                        <tr>
                            <td style="color: #71717a;">Discount</td>
                            <td class="text-right" style="color: #dc2626;">-{{ number_format((float) $quotation->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="color: #71717a;">Tax Base (DPP)</td>
                        <td class="text-right" style="font-weight: 600;">{{ number_format((float) $quotation->dpp, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #71717a;">VAT / PPN ({{ number_format((float) $quotation->vat_rate_applied, 1) }}%)</td>
                        <td class="text-right" style="font-weight: 600;">{{ number_format((float) $quotation->tax_amount, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>GRAND TOTAL ({{ $quotation->currency }})</td>
                        <td class="text-right">{{ number_format((float) $quotation->grand_total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signatures" cellpadding="0" cellspacing="0">
        <tr>
            <td class="signature-box">
                <div style="font-size: 7.5pt; color: #71717a; margin-bottom: 4px;">Prepared & Confirmed by,</div>
                <div class="company-title" style="font-size: 9pt;">PT. PANCA BIMA BERGAWA</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $quotation->approver ? $quotation->approver->name : ($quotation->user ? $quotation->user->name : 'Commercial Sales Department') }}</div>
                <div class="signature-role">{{ $quotation->is_approved_by_director ? 'Director / Authorized Officer' : 'Commercial Sales Officer' }}</div>
            </td>
            <td style="width: 10%;"></td>
            <td class="signature-box">
                <div style="font-size: 7.5pt; color: #71717a; margin-bottom: 4px;">Confirmed & Accepted by,</div>
                <div class="company-title" style="font-size: 9pt;">{{ $quotation->company_name }}</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $quotation->customer_name }}</div>
                <div class="signature-role">Authorized Client Representative</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        This quotation is an official electronic document issued by PT. Panca Bima Bergawa. Rasuna Said Kav. C5, Jakarta Selatan 12920.
    </div>
</body>
</html>
