<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice - {{ $invoice->invoice_number }}</title>
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
            width: 110px;
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
            width: 55%;
            vertical-align: top;
            padding-right: 14px;
        }
        .summary-column {
            width: 45%;
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
        .terms-box {
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
            padding: 8px 10px;
            font-size: 7.5pt;
            color: #52525b;
            line-height: 1.45;
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
            height: 55px;
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
            <td style="width: 60%; vertical-align: top;">
                <div class="company-title">PT. Panca Bima Bergawa</div>
                <div class="company-subtitle">
                    Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920<br>
                    NPWP: 01.234.567.8-012.000 | Phone: (021) 5290-8888 | Email: finance@bimabergawa.com
                </div>
            </td>
            <td class="doc-title-block">
                <div class="doc-title">Commercial Invoice</div>
                <div class="doc-meta">
                    Invoice #: <strong>{{ $invoice->invoice_number }}</strong><br>
                    Date: <strong>{{ optional($invoice->invoice_date)->format('d F Y') ?? now()->format('d F Y') }}</strong><br>
                    Due Date: <strong>{{ optional($invoice->due_date)->format('d F Y') ?? '-' }}</strong><br>
                    Status: <strong>{{ strtoupper($invoice->status ?? 'ISSUED') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-grid" cellpadding="0" cellspacing="0">
        <tr>
            <td class="info-box" style="margin-right: 8px;">
                <div class="info-box-title">Billed To (Customer Details)</div>
                <div class="info-row"><span class="info-label">Company Name:</span> <span class="info-value">{{ $invoice->company_name ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Attn / Contact:</span> <span class="info-value">{{ $invoice->customer_name ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Company NPWP:</span> <span class="info-value">{{ $invoice->company_npwp ?? '-' }}</span></div>
                @if($invoice->faktur_pajak_number)
                <div class="info-row"><span class="info-label">Faktur Pajak #:</span> <span class="info-value">{{ $invoice->faktur_pajak_number }}</span></div>
                @endif
            </td>
            <td style="width: 4%;"></td>
            <td class="info-box">
                <div class="info-box-title">Reference & Transaction Details</div>
                <div class="info-row"><span class="info-label">Sales Order #:</span> <span class="info-value">{{ $invoice->salesOrder?->order_number ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Quotation #:</span> <span class="info-value">{{ $invoice->quotation?->quotation_number ?? '-' }}</span></div>
                <div class="info-row"><span class="info-label">Payment Term:</span> <span class="info-value">{{ $invoice->paymentTerm?->term_name ?? 'Full CBD/TOP' }}</span></div>
                <div class="info-row"><span class="info-label">Currency:</span> <span class="info-value">{{ $invoice->currency ?? 'IDR' }}</span></div>
            </td>
        </tr>
    </table>

    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No</th>
                <th style="width: 20%;">SKU / Part #</th>
                <th style="width: 40%;">Product Description</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 10%; text-align: right;">Unit Price</th>
                <th style="width: 15%; text-align: right;">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace; font-weight: bold;">{{ $item->product?->sku ?? $item->product?->model_number ?? '-' }}</td>
                <td>
                    <strong>{{ $item->product?->name ?? 'Industrial Supply Item' }}</strong>
                    @if($item->notes)
                    <div style="font-size: 7.5pt; color: #71717a; margin-top: 2px;">{{ $item->notes }}</div>
                    @endif
                </td>
                <td class="text-center">{{ number_format((float) $item->quantity, 0) }} {{ $item->uom ?? 'pcs' }}</td>
                <td class="text-right">Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                <td class="text-right" style="font-weight: 600;">Rp {{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center" style="padding: 14px; color: #71717a;">
                    No line items recorded for this invoice.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-wrapper" cellpadding="0" cellspacing="0">
        <tr>
            <td class="terms-column">
                <div class="terms-box">
                    <strong>Payment Instructions & Bank Account Details:</strong><br>
                    Please transfer the invoice balance to our official corporate account:<br>
                    <strong>Bank Mandiri (Persero) Tbk</strong><br>
                    Account No: <strong>124-00-1122334-5</strong><br>
                    Account Name: <strong>PT. Panca Bima Bergawa</strong><br>
                    Branch: <strong>KCP Jakarta Rasuna Said</strong><br>
                    <em style="color: #71717a; font-size: 7pt; display: block; margin-top: 4px;">
                        Note: Please include the Invoice Number ({{ $invoice->invoice_number }}) in your payment description and send confirmation to finance@bimabergawa.com.
                    </em>
                </div>
            </td>
            <td class="summary-column">
                <table class="summary-table" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="color: #71717a;">Subtotal DPP:</td>
                        <td class="text-right" style="font-weight: 600;">Rp {{ number_format((float) $invoice->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #71717a;">VAT / PPN ({{ number_format((float) ($invoice->vat_rate_applied ?? 11), 1) }}%):</td>
                        <td class="text-right" style="font-weight: 600;">Rp {{ number_format((float) $invoice->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>Grand Total:</td>
                        <td class="text-right">Rp {{ number_format((float) $invoice->grand_total, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #059669; font-weight: 600;">Settled / Paid:</td>
                        <td class="text-right" style="color: #059669; font-weight: 600;">Rp {{ number_format((float) $invoice->paid_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #dc2626; font-weight: 600;">Remaining Balance:</td>
                        <td class="text-right" style="color: #dc2626; font-weight: bold;">Rp {{ number_format((float) $invoice->remaining_balance, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signatures" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%;"></td>
            <td class="signature-box" style="float: right;">
                <div style="font-size: 8pt; color: #71717a; margin-bottom: 4px;">Jakarta, {{ optional($invoice->invoice_date)->format('d F Y') ?? now()->format('d F Y') }}</div>
                <div style="font-size: 8.5pt; font-weight: bold; color: #09090b; margin-bottom: 6px;">PT. Panca Bima Bergawa</div>
                <div class="signature-space"></div>
                <div class="signature-name">Authorized Finance Director</div>
                <div class="signature-role">Commercial & Treasury Division</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        This is an official computer-generated commercial document issued by PT. Panca Bima Bergawa. Page 1 of 1.
    </div>
</body>
</html>
