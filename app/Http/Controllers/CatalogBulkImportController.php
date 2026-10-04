<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ProductTemplateExport;
use App\Imports\ProductsBulkImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CatalogBulkImportController extends Controller
{
    /**
     * Download the official Excel import template.
     * (Rujukan: TC-CAT-IMP-001)
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new ProductTemplateExport, 'catalog_template.xlsx');
    }

    /**
     * Handle bulk catalog import execution.
     * (Rujukan: TC-CAT-IMP-002, TC-CAT-IMP-007 s.d. TC-CAT-IMP-016)
     */
    public function import(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['super_admin', 'sales_pic']),
            403,
            'Unauthorized access to bulk catalog import.'
        );

        $file = $request->file('file');
        if (! $file) {
            return response()->json([
                'success' => false,
                'message' => 'File is required.',
            ], 422);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'csv', 'xls'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Format berkas wajib berupa XLSX atau CSV (File format must be XLSX or CSV).',
                'errors' => [
                    'file' => ['Format berkas wajib berupa XLSX atau CSV (File format must be XLSX or CSV).'],
                ],
            ], 422);
        }

        try {
            $importer = new ProductsBulkImport;
            Excel::import($importer, $file);
            $importer->recordActivityLog();

            return response()->json([
                'success' => true,
                'message' => sprintf('Import Selesai: %d produk berhasil diproses', $importer->getTotalProcessed()),
                'created' => $importer->createdCount,
                'updated' => $importer->updatedCount,
                'skipped' => $importer->skippedCount,
                'errors' => $importer->errors,
            ]);
        } catch (ValidationException $ve) {
            return response()->json([
                'success' => false,
                'message' => $ve->getMessage(),
                'errors' => $ve->errors(),
            ], 422);
        }
    }
}
