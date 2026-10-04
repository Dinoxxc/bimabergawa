<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sales Order - {{ $salesOrder->order_number }}</title>
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
            width: 105px;
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
                <div class="doc-title">SALES ORDER</div>
                <div class="doc-meta">
                    <strong>SO No:</strong> {{ $salesOrder->order_number }}<br>
                    <strong>Date:</strong> {{ $salesOrder->created_at ? $salesOrder->created_at->format('d M Y') : now()->format('d M Y') }}<br>
                    <strong>PO No:</strong> {{ $salesOrder->customer_po_number ?: '-' }}<br>
                    <strong>PO Date:</strong> {{ $salesOrder->customer_po_date ? $salesOrder->customer_po_date->format('d M Y') : '-' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="info-grid" cellpadding="0" cellspacing="0">
        <tr>
            <td class="info-box" style="margin-right: 6px;">
                <div class="info-box-title">Client Information</div>
                <div class="info-row"><span class="info-label">Company Name:</span> <span class="info-value">{{ $quotation ? $quotation->company_name : '-' }}</span></div>
                <div class="info-row"><span class="info-label">Contact Person:</span> <span class="info-value">{{ $quotation ? $quotation->customer_name : '-' }}</span></div>
                <div class="info-row"><span class="info-label">Quotation Ref:</span> <span class="info-value">{{ $quotation ? $quotation->quotation_number : '-' }}</span></div>
            </td>
            <td style="width: 12px;"></td>
            <td class="info-box">
                <div class="info-box-title">Order & Logistics Terms</div>
                <div class="info-row"><span class="info-label">Project Title:</span> <span class="info-value">{{ $salesOrder->project_name ?: ($quotation ? $quotation->project_name : 'General Supply') }}</span></div>
                <div class="info-row"><span class="info-label">Delivery Terms:</span> <span class="info-value">{{ $quotation ? ($quotation->delivery_terms ?: 'Loco Warehouse PBB Jakarta') : 'Loco Warehouse PBB Jakarta' }}</span></div>
                <div class="info-row"><span class="info-label">Currency:</span> <span class="info-value">{{ $salesOrder->currency ?: 'IDR' }}</span></div>
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
                <th class="text-right" style="width: 80px;">Unit Price ({{ $salesOrder->currency }})</th>
                <th class="text-center" style="width: 45px;">Disc</th>
                <th class="text-right" style="width: 95px;">Total ({{ $salesOrder->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @if($quotation && $quotation->items->isNotEmpty())
                @foreach($quotation->items as $index => $item)
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
                        </td>
                        <td class="text-center">{{ number_format((float) $item->quantity, 0) }}</td>
                        <td class="text-center">{{ strtoupper($item->uom ?: 'PCS') }}</td>
                        <td class="text-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                        <td class="text-center">{{ $item->discount_percent > 0 ? number_format((float) $item->discount_percent, 1).'%' : '-' }}</td>
                        <td class="text-right" style="font-weight: bold;">{{ number_format((float) $item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="8" class="text-center" style="padding: 12px; color: #71717a;">No items registered in reference quotation.</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="summary-wrapper" cellpadding="0" cellspacing="0">
        <tr>
            <td class="terms-column">
                <div class="section-heading">Fulfillment Notes & Commercial Conditions</div>
                <div class="terms-box">
                    <ol>
                        <li>Fulfillment and dispatch will be scheduled upon warehouse preparation and delivery order issuance.</li>
                        <li>Partial deliveries are permitted as mutually agreed between both parties.</li>
                        <li>Goods inspection should be performed upon handover alongside authorized Delivery Order (Surat Jalan).</li>
                    </ol>
                </div>
            </td>
            <td class="summary-column">
                <table class="summary-table" cellpadding="0" cellspacing="0">
                    @if($quotation)
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
                            <td style="color: #71717a;">VAT / PPN ({{ number_format((float) $salesOrder->vat_rate_applied, 1) }}%)</td>
                            <td class="text-right" style="font-weight: 600;">{{ number_format((float) $quotation->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="total-row">
                        <td>TOTAL AMOUNT ({{ $salesOrder->currency }})</td>
                        <td class="text-right">{{ number_format((float) $salesOrder->total_amount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signatures" cellpadding="0" cellspacing="0">
        <tr>
            <td class="signature-box">
                <div style="font-size: 7.5pt; color: #71717a; margin-bottom: 4px;">Order Prepared & Processed by,</div>
                <div class="company-title" style="font-size: 9pt;">PT. PANCA BIMA BERGAWA</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $salesOrder->approver ? $salesOrder->approver->name : ($quotation && $quotation->user ? $quotation->user->name : 'Commercial Sales Department') }}</div>
                <div class="signature-role">{{ $salesOrder->is_approved_by_director ? 'Director / Authorized Officer' : 'Commercial Operations' }}</div>
            </td>
            <td style="width: 10%;"></td>
            <td class="signature-box">
                <div style="font-size: 7.5pt; color: #71717a; margin-bottom: 4px;">Acknowledged by Client,</div>
                <div class="company-title" style="font-size: 9pt;">{{ $quotation ? $quotation->company_name : 'Customer Entity' }}</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $quotation ? $quotation->customer_name : 'Authorized Signatory' }}</div>
                <div class="signature-role">Authorized Client Officer</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Official Sales Order document issued by PT. Panca Bima Bergawa. Rasuna Said Kav. C5, Jakarta Selatan 12920.
    </div>
</body>
</html>
