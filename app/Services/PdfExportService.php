<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Quotation;
use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfInstance;
use Illuminate\Support\Facades\Storage;

class PdfExportService
{
    /**
     * Generate official Quotation PDF compliant with corporate standards.
     */
    public static function generateQuotationPdf(Quotation $quotation, bool $saveToStorage = true): DomPdfInstance
    {
        $quotation->loadMissing(['items.product', 'inquiry', 'user', 'approver']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);

        if ($saveToStorage) {
            $safeNumber = str_replace(['/', '\\'], '-', $quotation->quotation_number);
            $filename = sprintf('quotation_%s.pdf', $safeNumber);
            $relativePath = 'quotations/pdf/'.$filename;

            // Simpan ke storage privat
            Storage::disk('local_private')->put($relativePath, $pdf->output());

            if ($quotation->pdf_path !== $relativePath) {
                $quotation->pdf_path = $relativePath;
                $quotation->saveQuietly();
            }
        }

        return $pdf;
    }

    /**
     * Generate official Sales Order PDF compliant with corporate standards.
     */
    public static function generateSalesOrderPdf(SalesOrder $salesOrder, bool $saveToStorage = true): DomPdfInstance
    {
        $salesOrder->loadMissing(['quotation.items.product', 'quotation.inquiry', 'approver']);

        $pdf = Pdf::loadView('pdf.sales-order', [
            'salesOrder' => $salesOrder,
            'quotation' => $salesOrder->quotation,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);

        if ($saveToStorage) {
            $safeNumber = str_replace(['/', '\\'], '-', $salesOrder->order_number);
            $filename = sprintf('sales_order_%s.pdf', $safeNumber);
            $relativePath = 'sales_orders/pdf/'.$filename;

            Storage::disk('local_private')->put($relativePath, $pdf->output());
        }

        return $pdf;
    }

    /**
     * Generate official Delivery Order (Surat Jalan) PDF compliant with corporate standards.
     * Guaranteed zero-price display for logistics and physical delivery.
     */
    public static function generateDeliveryOrderPdf(DeliveryOrder $deliveryOrder, bool $saveToStorage = true): DomPdfInstance
    {
        $deliveryOrder->loadMissing([
            'items.product.brand',
            'salesOrder.quotation.inquiry',
        ]);

        $pdf = Pdf::loadView('pdf.delivery-order', [
            'deliveryOrder' => $deliveryOrder,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);

        if ($saveToStorage) {
            $safeNumber = str_replace(['/', '\\'], '-', $deliveryOrder->delivery_number);
            $filename = sprintf('delivery_order_%s.pdf', $safeNumber);
            $relativePath = 'delivery_orders/pdf/'.$filename;

            Storage::disk('local_private')->put($relativePath, $pdf->output());
        }

        return $pdf;
    }
}
