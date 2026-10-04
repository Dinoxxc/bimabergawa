<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Delivery Order - {{ $deliveryOrder->delivery_number }}</title>
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
            font-size: 15pt;
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
            page-break-inside: avoid;
            margin-top: 10px;
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
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 33.33%;
            vertical-align: top;
            text-align: center;
        }
        .signature-space {
            height: 55px;
        }
        .signature-name {
            font-weight: bold;
            font-size: 8pt;
            color: #09090b;
            border-top: 1px solid #71717a;
            padding-top: 4px;
            display: inline-block;
            min-width: 140px;
        }
        .signature-role {
            font-size: 7.5pt;
            color: #71717a;
            margin-top: 2px;
        }
        .footer-note {
            margin-top: 14px;
            font-size: 7pt;
            color: #a1a1aa;
            text-align: center;
            border-top: 1px dashed #e4e4e7;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    {{-- Header Kop Rasuna Said --}}
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 58%; vertical-align: top;">
                <div class="company-title">PT. Panca Bima Bergawa</div>
                <div class="company-subtitle">
                    Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi<br>
                    Jakarta Selatan 12920 - DKI Jakarta, Indonesia<br>
                    Telp: +62 21 521 3456 | Email: sales@bimabergawa.com | Web: bimabergawa.com
                </div>
            </td>
            <td class="doc-title-block" style="width: 42%;">
                <div class="doc-title">Surat Jalan</div>
                <div style="font-size: 9pt; font-weight: 600; color: #52525b; text-transform: uppercase; margin-top: 1px;">
                    Delivery Order
                </div>
                <div class="doc-meta">
                    DO #: <strong>{{ $deliveryOrder->delivery_number }}</strong><br>
                    Date: <strong>{{ ($deliveryOrder->shipped_at ?? $deliveryOrder->created_at)->format('d F Y') }}</strong><br>
                    Status: <strong>[{{ strtoupper(str_replace('_', ' ', $deliveryOrder->status)) }}]</strong>
                </div>
            </td>
        </tr>
    </table>

    {{-- Info Grid: Recipient & Logistics Details --}}
    <table class="info-grid" cellpadding="0" cellspacing="0">
        <tr>
            {{-- Recipient & Destination --}}
            <td class="info-box" style="padding-right: 12px;">
                <div class="info-box-title">Shipping & Recipient Details</div>
                <div class="info-row">
                    <span class="info-label">Recipient PIC:</span>
                    <span class="info-value">{{ $deliveryOrder->recipient_pic_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone / Mobile:</span>
                    <span class="info-value">{{ $deliveryOrder->recipient_phone }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Destination:</span>
                    <span class="info-value" style="font-size: 8pt; font-weight: normal;">{{ $deliveryOrder->shipping_address }}</span>
                </div>
                <div class="info-row" style="margin-top: 4px;">
                    <span class="info-label">Customer PO #:</span>
                    <span class="info-value">{{ $deliveryOrder->salesOrder->customer_po_number ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">SO Reference:</span>
                    <span class="info-value">{{ $deliveryOrder->salesOrder->order_number }}</span>
                </div>
            </td>

            <td style="width: 14px;"></td>

            {{-- Expedition & Transport Details --}}
            <td class="info-box" style="padding-left: 12px;">
                <div class="info-box-title">Carrier & Expedition Details</div>
                <div class="info-row">
                    <span class="info-label">Carrier / Expedition:</span>
                    <span class="info-value">{{ $deliveryOrder->courier_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Driver Name:</span>
                    <span class="info-value">{{ $deliveryOrder->driver_name ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Vehicle Plate #:</span>
                    <span class="info-value">{{ $deliveryOrder->vehicle_plate_number ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tracking / AWB #:</span>
                    <span class="info-value">{{ $deliveryOrder->tracking_number ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Packages:</span>
                    <span class="info-value">{{ $deliveryOrder->total_packages ?? 1 }} Packages</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Weight:</span>
                    <span class="info-value">{{ $deliveryOrder->total_weight_kg ? number_format((float) $deliveryOrder->total_weight_kg, 2).' Kg' : '-' }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Line Items Table (Physical items only, 0% price leakage) --}}
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 32px;" class="text-center">No</th>
                <th style="width: 120px;">Part Number / SKU</th>
                <th>Item Description</th>
                <th style="width: 80px;" class="text-center">Qty Shipped</th>
                <th style="width: 60px;" class="text-center">UOM</th>
                <th style="width: 110px;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deliveryOrder->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: monospace; font-size: 7.5pt; font-weight: bold;">
                        {{ $item->product?->sku ?? $item->model_number ?? '-' }}
                    </td>
                    <td>
                        <strong>{{ $item->item_name }}</strong>
                        @if ($item->product && $item->product->brand)
                            <br><span style="font-size: 7pt; color: #71717a;">Brand: {{ $item->product->brand->name }}</span>
                        @endif
                    </td>
                    <td class="text-center" style="font-weight: bold; font-size: 8.5pt;">
                        {{ number_format($item->quantity_shipped) }}
                    </td>
                    <td class="text-center" style="text-transform: uppercase;">
                        {{ $item->uom ?? 'PCS' }}
                    </td>
                    <td style="font-size: 7pt; color: #52525b;">
                        @if ($item->quantity_received > 0 && $item->quantity_defective > 0)
                            Recv: {{ $item->quantity_received }} | Def: {{ $item->quantity_defective }}
                        @elseif ($item->quantity_received > 0)
                            Recv: {{ $item->quantity_received }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 12px; color: #71717a;">
                        No delivery items specified.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Terms and Conditions --}}
    <div class="terms-box">
        <div class="section-heading">Syarat & Ketentuan Serah Terima Fisik Barang:</div>
        <ol>
            <li>Barang-barang yang tercantum di atas telah diperiksa dan diserahkan dalam keadaan baik, lengkap, dan baru.</li>
            <li>Surat Jalan ini merupakan bukti serah terima fisik barang yang sah antara PT. Panca Bima Bergawa dengan penerima kuasa proyek.</li>
            <li>Segala bentuk klaim atas ketidaksesuaian jumlah unit, kekurangan fisik, atau cacat wajib dicatatkan pada kolom keterangan Surat Jalan ini pada saat serah terima di lokasi.</li>
            <li>Penerima wajib membubuhkan tanda tangan jelas, nama terang, nomor kontak, serta stempel resmi instansi/proyek pemesan.</li>
        </ol>
    </div>

    {{-- Signatures Block --}}
    <table class="signatures" cellpadding="0" cellspacing="0">
        <tr>
            <td class="signature-box">
                <div style="font-size: 8pt; font-weight: bold; color: #52525b; margin-bottom: 4px;">Disiapkan Oleh:</div>
                <div class="signature-space"></div>
                <div class="signature-name">( _________________________ )</div>
                <div class="signature-role">Logistik & Pergudangan PBB</div>
            </td>
            <td class="signature-box">
                <div style="font-size: 8pt; font-weight: bold; color: #52525b; margin-bottom: 4px;">Diserahkan Oleh:</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $deliveryOrder->driver_name ? "( {$deliveryOrder->driver_name} )" : '( _________________________ )' }}</div>
                <div class="signature-role">Ekspedisi / Kurir Pengantar</div>
            </td>
            <td class="signature-box">
                <div style="font-size: 8pt; font-weight: bold; color: #52525b; margin-bottom: 4px;">Diterima Oleh:</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $deliveryOrder->recipient_pic_name ? "( {$deliveryOrder->recipient_pic_name} )" : '( _________________________ )' }}</div>
                <div class="signature-role">Tanda Tangan & Stempel Penerima</div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini diterbitkan secara sah melalui Sistem Operasional Internal ERP PT. Panca Bima Bergawa. Tanpa informasi harga komersial.
    </div>

</body>
</html>
