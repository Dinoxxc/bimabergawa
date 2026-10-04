<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quotation;
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
}
