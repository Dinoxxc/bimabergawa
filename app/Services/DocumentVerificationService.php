<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\SalesOrder;

class DocumentVerificationService
{
    /**
     * Generate a cryptographic SHA-256 verification hash for transaction documents.
     */
    public static function generateHash(string $documentNumber, string $createdAt): string
    {
        $appKey = (string) config('app.key', 'pbb-secure-token');

        return hash('sha256', $documentNumber.$createdAt.$appKey);
    }

    /**
     * Get the full public verification URL for a given document.
     */
    public static function getVerificationUrl(string $type, string $documentNumber, string $createdAt): string
    {
        $hash = self::generateHash($documentNumber, $createdAt);

        return url("/verify-doc/{$type}/{$hash}");
    }

    /**
     * Verify document authenticity across corporate transaction models.
     *
     * @return array<string, mixed>|null
     */
    public static function verify(string $type, string $hash): ?array
    {
        return match ($type) {
            'quotation' => self::verifyQuotation($hash),
            'sales-order' => self::verifySalesOrder($hash),
            'delivery-order' => self::verifyDeliveryOrder($hash),
            'invoice' => self::verifyInvoice($hash),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function verifyQuotation(string $hash): ?array
    {
        $quotations = Quotation::with(['inquiry', 'items'])->latest()->take(200)->get();

        foreach ($quotations as $quotation) {
            $expectedHash = self::generateHash($quotation->quotation_number, (string) $quotation->created_at);
            if (hash_equals($expectedHash, $hash)) {
                return [
                    'type' => 'Quotation (Penawaran Resmi)',
                    'type_slug' => 'quotation',
                    'document_number' => $quotation->quotation_number,
                    'created_at' => $quotation->created_at?->format('d F Y, H:i').' WIB',
                    'client_company' => $quotation->company_name,
                    'customer_name' => $quotation->customer_name,
                    'status' => strtoupper((string) $quotation->status),
                    'total_amount' => 'Rp '.number_format((float) $quotation->grand_total, 0, ',', '.'),
                    'items_count' => $quotation->items->count(),
                    'is_valid' => true,
                    'authorized_by' => 'Direksi PT. Panca Bima Bergawa',
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function verifySalesOrder(string $hash): ?array
    {
        $salesOrders = SalesOrder::with(['quotation.items'])->latest()->take(200)->get();

        foreach ($salesOrders as $so) {
            $expectedHash = self::generateHash($so->order_number, (string) $so->created_at);
            if (hash_equals($expectedHash, $hash)) {
                return [
                    'type' => 'Sales Order (Konfirmasi Pesanan)',
                    'type_slug' => 'sales-order',
                    'document_number' => $so->order_number,
                    'created_at' => $so->created_at?->format('d F Y, H:i').' WIB',
                    'client_company' => $so->quotation?->company_name ?? 'Client Entity',
                    'customer_name' => $so->quotation?->customer_name ?? '-',
                    'status' => strtoupper((string) $so->status),
                    'total_amount' => 'Rp '.number_format((float) $so->total_amount, 0, ',', '.'),
                    'items_count' => $so->quotation?->items->count() ?? 0,
                    'is_valid' => true,
                    'authorized_by' => $so->approved_at ? 'Otorisasi Direktur Commercial PBB' : 'Auto-Approved System',
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function verifyDeliveryOrder(string $hash): ?array
    {
        $dos = DeliveryOrder::with(['salesOrder.quotation', 'items'])->latest()->take(200)->get();

        foreach ($dos as $do) {
            $expectedHash = self::generateHash($do->delivery_number, (string) $do->created_at);
            if (hash_equals($expectedHash, $hash)) {
                return [
                    'type' => 'Surat Jalan (Delivery Order)',
                    'type_slug' => 'delivery-order',
                    'document_number' => $do->delivery_number,
                    'created_at' => $do->created_at?->format('d F Y, H:i').' WIB',
                    'client_company' => $do->salesOrder?->quotation?->company_name ?? 'Client Entity',
                    'customer_name' => $do->recipient_pic_name ?? '-',
                    'status' => strtoupper((string) $do->status),
                    'total_amount' => 'Dispatched Physical Goods',
                    'items_count' => $do->items->count(),
                    'is_valid' => true,
                    'authorized_by' => 'Logistics Department PT. Panca Bima Bergawa',
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function verifyInvoice(string $hash): ?array
    {
        $invoices = Invoice::with(['salesOrder', 'items'])->latest()->take(200)->get();

        foreach ($invoices as $invoice) {
            $expectedHash = self::generateHash($invoice->invoice_number, (string) $invoice->created_at);
            if (hash_equals($expectedHash, $hash)) {
                return [
                    'type' => 'Faktur Tagihan (Commercial Invoice)',
                    'type_slug' => 'invoice',
                    'document_number' => $invoice->invoice_number,
                    'created_at' => $invoice->created_at?->format('d F Y, H:i').' WIB',
                    'client_company' => $invoice->company_name,
                    'customer_name' => $invoice->customer_name,
                    'status' => strtoupper((string) $invoice->status),
                    'total_amount' => 'Rp '.number_format((float) $invoice->grand_total, 0, ',', '.'),
                    'items_count' => $invoice->items->count(),
                    'is_valid' => true,
                    'authorized_by' => 'Finance & Accounting PT. Panca Bima Bergawa',
                ];
            }
        }

        return null;
    }
}
