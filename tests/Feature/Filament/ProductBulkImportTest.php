<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Exports\ProductTemplateExport;
use App\Imports\ProductsBulkImport;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Services\PdfExportService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ProductBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $salesPic;

    protected User $logisticsUser;

    protected User $mitraUser;

    protected Brand $brandSchneider;

    protected Category $categoryElectrical;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->salesPic = User::factory()->create([
            'email' => 'sales@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->salesPic->assignRole('sales_pic');

        $this->logisticsUser = User::factory()->create([
            'email' => 'logistics@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->logisticsUser->assignRole('logistics');

        $this->mitraUser = User::factory()->create([
            'email' => 'mitra@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->mitraUser->assignRole('freelance_sales');

        $this->brandSchneider = Brand::create([
            'name' => 'Schneider Electric',
            'code' => '001',
            'slug' => 'schneider-electric',
            'is_active' => true,
        ]);

        $this->categoryElectrical = Category::create([
            'name' => 'Electrical & Power',
            'code' => 'EL',
            'slug' => 'electrical-power',
        ]);
    }

    /**
     * TC-CAT-IMP-001: Unduh Berkas Templat Resmi Import Excel Katalog.
     */
    public function test_can_download_official_catalog_template_with_standard_headers(): void
    {
        $export = new ProductTemplateExport;
        $headings = $export->headings();

        $expected = [
            'brand_name',
            'category_name',
            'sku',
            'name',
            'base_hpp',
            'recommend_price',
            'unit',
            'description',
        ];

        $this->assertEquals($expected, $headings);
        $this->assertNotEmpty($export->array());

        $response = $this->actingAs($this->superAdmin)
            ->get(route('catalog.template.download'));

        $response->assertOk();
    }

    /**
     * TC-CAT-IMP-002: Eksekusi Import Massal Excel Data Bersih (Chunking 100 Baris & RAM < 64 MB).
     */
    public function test_chunked_import_processes_rows_with_memory_under_64mb(): void
    {
        $rows = [];
        for ($i = 1; $i <= 500; $i++) {
            $rows[] = [
                'brand_name' => 'Schneider Electric',
                'category_name' => 'Electrical & Power',
                'sku' => sprintf('SCH-MCB-%04d', $i),
                'name' => sprintf('Schneider MCB EZC %04d', $i),
                'base_hpp' => 50000 + $i,
                'recommend_price' => 75000 + $i,
                'unit' => 'pcs',
                'description' => 'Moulded case circuit breaker test item',
            ];
        }

        $importer = new ProductsBulkImport;
        $this->assertSame(100, $importer->chunkSize());

        $startMemory = memory_get_usage();

        // Process in 5 chunks of 100 rows
        $chunks = array_chunk($rows, 100);
        foreach ($chunks as $chunk) {
            $importer->collection(new Collection($chunk));
            unset($chunk);
        }

        $memoryConsumedMb = (memory_get_usage() - $startMemory) / 1024 / 1024;
        $this->assertLessThan(64, $memoryConsumedMb, 'Memory consumed during 500 rows import exceeded 64 MB.');

        $this->assertSame(500, $importer->createdCount);
        $this->assertSame(0, $importer->updatedCount);
        $this->assertSame(500, Product::count());
    }

    /**
     * TC-CAT-IMP-003: Normalisasi Otomatis Format Kode SKU.
     */
    public function test_automatic_sku_normalization_uppercase_and_hyphenation(): void
    {
        $importer = new ProductsBulkImport;
        $row = [
            'brand_name' => 'Schneider Electric',
            'category_name' => 'Electrical & Power',
            'sku' => '  sch-mcb_16a  ',
            'name' => 'Miniature Circuit Breaker 16A',
            'base_hpp' => 45000,
            'unit' => 'pcs',
        ];

        $importer->collection(new Collection([$row]));

        $product = Product::first();
        $this->assertNotNull($product);
        $this->assertSame('SCH-MCB-16A', $product->sku);
    }

    /**
     * TC-CAT-IMP-004: Pencocokan Otomatis Nama Brand & Kategori Eksisting.
     */
    public function test_automatic_matching_of_existing_brand_and_category_by_name(): void
    {
        $importer = new ProductsBulkImport;
        $row = [
            'brand_name' => 'Schneider Electric',
            'category_name' => 'Electrical & Power',
            'sku' => 'SCH-EZC-100',
            'name' => 'Schneider MCCB Easypact',
            'base_hpp' => 350000,
            'unit' => 'pcs',
        ];

        $importer->collection(new Collection([$row]));

        $product = Product::first();
        $this->assertNotNull($product);
        $this->assertSame($this->brandSchneider->id, $product->brand_id);
        $this->assertSame($this->categoryElectrical->id, $product->category_id);
    }

    /**
     * TC-CAT-IMP-005: Pembaruan Otomatis Produk Eksisting (Upsert by SKU).
     */
    public function test_upsert_by_sku_updates_existing_product_without_creating_duplicates(): void
    {
        $existingProduct = Product::create([
            'brand_id' => $this->brandSchneider->id,
            'category_id' => $this->categoryElectrical->id,
            'name' => 'Schneider Old MCB 16A',
            'model_number' => 'SCH-MCB-16A',
            'sku' => 'SCH-MCB-16A',
            'cost_price' => 50000.00,
            'uom' => 'pcs',
            'availability_status' => 'ready_stock',
        ]);

        $this->assertSame(1, Product::count());

        $importer = new ProductsBulkImport;
        $row = [
            'brand_name' => 'Schneider Electric',
            'category_name' => 'Electrical & Power',
            'sku' => 'SCH-MCB-16A',
            'name' => 'Schneider Updated MCB 16A Pro',
            'base_hpp' => 55000.00,
            'unit' => 'pcs',
        ];

        $importer->collection(new Collection([$row]));

        $this->assertSame(1, Product::count());
        $this->assertSame(1, $importer->updatedCount);
        $this->assertSame(0, $importer->createdCount);

        $existingProduct->refresh();
        $this->assertSame('Schneider Updated MCB 16A Pro', $existingProduct->name);
        $this->assertEquals(55000.00, (float) $existingProduct->cost_price);
    }

    /**
     * TC-CAT-IMP-006: Pembuatan Otomatis Brand Baru jika Belum Terdaftar.
     */
    public function test_automatic_creation_of_new_brand_if_not_found(): void
    {
        $this->assertNull(Brand::where('name', 'Siemens')->first());

        $importer = new ProductsBulkImport;
        $row = [
            'brand_name' => 'Siemens',
            'category_name' => 'Electrical & Power',
            'sku' => 'SIE-MCB-32A',
            'name' => 'Siemens Sentron MCB 32A',
            'base_hpp' => 80000,
            'unit' => 'pcs',
        ];

        $importer->collection(new Collection([$row]));

        $brand = Brand::where('name', 'Siemens')->first();
        $this->assertNotNull($brand);
        $this->assertSame('siemens', $brand->slug);
        $this->assertTrue($brand->is_active);

        $product = Product::where('sku', 'SIE-MCB-32A')->first();
        $this->assertNotNull($product);
        $this->assertSame($brand->id, $product->brand_id);
    }

    /**
     * TC-CAT-IMP-007: Validasi Penolakan Berkas dengan Header Kolom Tidak Standar.
     */
    public function test_rejection_of_file_with_unrecognized_corrupt_headers(): void
    {
        $importer = new ProductsBulkImport;
        $corruptRow = [
            'unknown_col_1' => 'Random Value 1',
            'unknown_col_2' => 'Random Value 2',
            'gibberish' => 'Test',
        ];

        $this->expectException(ValidationException::class);
        $importer->collection(new Collection([$corruptRow]));
    }

    /**
     * TC-CAT-IMP-008: Pelaporan Baris Data Tidak Valid (Detailed Error Reporting).
     */
    public function test_invalid_numeric_hpp_skips_row_and_reports_detailed_error(): void
    {
        $importer = new ProductsBulkImport;
        $rows = [
            [
                'brand_name' => 'Schneider Electric',
                'category_name' => 'Electrical & Power',
                'sku' => 'VALID-SKU-01',
                'name' => 'Valid Product 1',
                'base_hpp' => 50000,
                'unit' => 'pcs',
            ],
            [
                'brand_name' => 'Schneider Electric',
                'category_name' => 'Electrical & Power',
                'sku' => 'INVALID-SKU-02',
                'name' => 'Invalid Product with Text HPP',
                'base_hpp' => 'GRATIS',
                'unit' => 'pcs',
            ],
            [
                'brand_name' => 'Schneider Electric',
                'category_name' => 'Electrical & Power',
                'sku' => 'VALID-SKU-03',
                'name' => 'Valid Product 3',
                'base_hpp' => 75000,
                'unit' => 'pcs',
            ],
        ];

        $importer->collection(new Collection($rows));

        $this->assertSame(2, $importer->createdCount);
        $this->assertSame(1, $importer->skippedCount);
        $this->assertCount(1, $importer->errors);
        $this->assertStringContainsString('Nilai Base HPP wajib berupa angka numerik', $importer->errors[0]);
    }

    /**
     * TC-CAT-IMP-009: Penolakan Unggahan Format Berkas Bukan Spreadsheet.
     */
    public function test_rejection_of_non_spreadsheet_file_formats(): void
    {
        $fakePdf = UploadedFile::fake()->create('daftar-harga.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->superAdmin)
            ->postJson(route('catalog.bulk.import'), [
                'file' => $fakePdf,
            ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Format berkas wajib berupa XLSX atau CSV', $response->json('message'));
    }

    /**
     * TC-CAT-IMP-010: Penolakan Berkas Spreadsheet Kosong (0 Baris Data).
     */
    public function test_empty_spreadsheet_records_warning_notification(): void
    {
        $importer = new ProductsBulkImport;
        $importer->collection(new Collection([]));

        $this->assertCount(1, $importer->errors);
        $this->assertStringContainsString('Spreadsheet file does not contain any product data rows', $importer->errors[0]);
    }

    /**
     * TC-CAT-IMP-011: Perlindungan Terhadap Formula / CSV Injection (DDE Attack).
     */
    public function test_neutralization_of_formula_injection_attempts(): void
    {
        $importer = new ProductsBulkImport;
        $maliciousRow = [
            'brand_name' => 'Schneider Electric',
            'category_name' => 'Electrical & Power',
            'sku' => 'DDE-TEST-01',
            'name' => "=CMD|' /C calc'!A0",
            'base_hpp' => 100000,
            'unit' => 'pcs',
            'description' => '+SUM(1+1)*cmd|\' /C notepad\'!A0',
        ];

        $importer->collection(new Collection([$maliciousRow]));

        $product = Product::where('sku', 'DDE-TEST-01')->first();
        $this->assertNotNull($product);
        $this->assertStringStartsWith("'", $product->name);
        $this->assertStringStartsWith("'", $product->description);
    }

    /**
     * TC-CAT-IMP-012: Pembatasan Akses Fitur Import Khusus Role Super Admin & Sales PIC.
     */
    public function test_rbac_access_restriction_blocks_logistics_and_mitra(): void
    {
        $fakeFile = UploadedFile::fake()->create('test.xlsx', 50);

        // Logistics blocked
        $response = $this->actingAs($this->logisticsUser)
            ->postJson(route('catalog.bulk.import'), [
                'file' => $fakeFile,
            ]);
        $response->assertStatus(403);

        // Mitra blocked
        $response = $this->actingAs($this->mitraUser)
            ->postJson(route('catalog.bulk.import'), [
                'file' => $fakeFile,
            ]);
        $response->assertStatus(403);

        // Sales PIC allowed
        Excel::fake();
        $response = $this->actingAs($this->salesPic)
            ->postJson(route('catalog.bulk.import'), [
                'file' => $fakeFile,
            ]);
        $response->assertOk();
    }

    /**
     * TC-CAT-IMP-014: Rollback Transaksi Database pada Kegagalan Chunk Kritis.
     */
    public function test_database_transaction_rolls_back_on_critical_chunk_failure(): void
    {
        $importer = new ProductsBulkImport;
        $rows = [
            [
                'brand_name' => 'Schneider Electric',
                'category_name' => 'Electrical & Power',
                'sku' => 'ROLLBACK-01',
                'name' => 'Rollback Test Product 1',
                'base_hpp' => 50000,
                'unit' => 'pcs',
            ],
            [
                'brand_name' => 'Schneider Electric',
                'category_name' => 'Electrical & Power',
                'sku' => 'ROLLBACK-02',
                'name' => 'Rollback Test Product 2',
                'base_hpp' => 60000,
                'unit' => 'pcs',
            ],
        ];

        try {
            DB::transaction(function () use ($importer, $rows) {
                $importer->collection(new Collection($rows));
                // Simulate critical query failure
                throw new \RuntimeException('Simulated database connection failure in chunk');
            });
        } catch (\RuntimeException $e) {
            // Expected simulation
        }

        $this->assertNull(Product::where('sku', 'ROLLBACK-01')->first());
        $this->assertNull(Product::where('sku', 'ROLLBACK-02')->first());
    }

    /**
     * TC-CAT-IMP-015: Pencatatan Lengkap Eksekusi Import pada ActivityLog.
     */
    public function test_import_execution_is_recorded_in_activity_log(): void
    {
        $this->actingAs($this->superAdmin);

        $importer = new ProductsBulkImport;
        $row = [
            'brand_name' => 'Schneider Electric',
            'category_name' => 'Electrical & Power',
            'sku' => 'AUDIT-SKU-01',
            'name' => 'Audit Test Product',
            'base_hpp' => 99000,
            'unit' => 'pcs',
        ];

        $importer->collection(new Collection([$row]));
        $log = $importer->recordActivityLog();

        $this->assertNotNull($log);
        $this->assertSame('catalog_bulk_import', $log->log_name);
        $this->assertSame($this->superAdmin->id, $log->causer_id);
        $this->assertSame(1, $log->properties['created']);

        $dbLog = ActivityLog::where('log_name', 'catalog_bulk_import')->first();
        $this->assertNotNull($dbLog);
        $this->assertStringContainsString('Ringkasan: 1 Berhasil Ditambahkan', $dbLog->description);
    }

    /**
     * TC-CAT-IMP-017: Pembangkitan Otomatis SKU Internal PBB Berformat 12 Karakter [TIPE][DIV][BRAND][SEQUENCE].
     */
    public function test_automatic_generation_of_12_char_pbb_sku_when_sku_omitted(): void
    {
        $importer = new ProductsBulkImport;
        $row = [
            'brand_name' => 'Schneider Electric',
            'category_name' => 'Electrical & Power',
            'sku' => '', // Empty SKU, should auto-generate 12-char PBB SKU
            'name' => 'Auto Generated SKU Product',
            'base_hpp' => 125000,
            'unit' => 'pcs',
        ];

        $importer->collection(new Collection([$row]));

        $product = Product::where('name', 'Auto Generated SKU Product')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->sku);
        $this->assertSame(12, strlen($product->sku));
        // Type: S, Category: EL, Brand: 001, Sequence: 000001 -> SEL001000001
        $this->assertSame('SEL001000001', $product->sku);
    }

    /**
     * TC-CAT-IMP-018: Proteksi Immutabilitas SKU Internal PBB & Normalisasi saat Pembaruan Produk.
     */
    public function test_immutability_of_sku_on_product_update_when_sku_is_empty(): void
    {
        $existingProduct = Product::create([
            'brand_id' => $this->brandSchneider->id,
            'category_id' => $this->categoryElectrical->id,
            'name' => 'Existing Product with PBB SKU',
            'model_number' => 'EZC100F-MFR',
            'sku' => 'SEL001000001',
            'cost_price' => 100000.00,
            'uom' => 'pcs',
            'availability_status' => 'ready_stock',
        ]);

        $importer = new ProductsBulkImport;
        $row = [
            'brand_name' => 'Schneider Electric',
            'category_name' => 'Electrical & Power',
            'sku' => 'EZC100F-MFR', // matches by model_number
            'name' => 'Existing Product with Updated Name',
            'base_hpp' => 110000.00,
            'unit' => 'pcs',
        ];

        $importer->collection(new Collection([$row]));

        $existingProduct->refresh();
        $this->assertSame('SEL001000001', $existingProduct->sku, 'Original internal SKU must not be overwritten with empty value.');
        $this->assertSame('Existing Product with Updated Name', $existingProduct->name);
        $this->assertEquals(110000.00, (float) $existingProduct->cost_price);
    }

    /**
     * TC-CAT-IMP-019: Pewarisan Hierarkis Kode Divisi Kategori Induk saat Sub-kategori Tidak Berkode.
     */
    public function test_hierarchical_inheritance_of_division_code_from_parent_category(): void
    {
        $parentCat = Category::create([
            'name' => 'Industrial Automation',
            'code' => 'AU',
            'slug' => 'industrial-automation',
        ]);

        $subCat = Category::create([
            'parent_id' => $parentCat->id,
            'name' => 'Inverters & Drives',
            'code' => null, // Empty code, must inherit 'AU'
            'slug' => 'inverters-drives',
        ]);

        $importer = new ProductsBulkImport;
        $row = [
            'brand_name' => 'Schneider Electric',
            'category_name' => 'Inverters & Drives',
            'sku' => '', // Auto generate
            'name' => 'Schneider Altivar ATV310 5.5kW',
            'base_hpp' => 4500000,
            'unit' => 'unit',
        ];

        $importer->collection(new Collection([$row]));

        $product = Product::where('name', 'Schneider Altivar ATV310 5.5kW')->first();
        $this->assertNotNull($product);
        $this->assertSame(12, strlen($product->sku));
        // Type: S, Category: AU (inherited from parent), Brand: 001, Sequence: 000001 -> SAU001000001
        $this->assertSame('SAU001000001', $product->sku);
    }

    /**
     * Verification of DomPDF Invoice Generation.
     */
    public function test_can_generate_official_invoice_dompdf_document(): void
    {
        $inquiry = Inquiry::create([
            'inquiry_number' => 'PBB/INQ/2026/10/0001',
            'customer_name' => 'Budi Santoso',
            'company_name' => 'PT. Mega Graha Mandiri',
            'phone_wa' => '081234567890',
            'email' => 'finance@megagraha.co.id',
            'status' => 'deal',
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/10/0001',
            'inquiry_id' => $inquiry->id,
            'user_id' => $this->superAdmin->id,
            'company_name' => 'PT. Mega Graha Mandiri',
            'customer_name' => 'Budi Santoso',
            'customer_phone' => '081234567890',
            'customer_email' => 'finance@megagraha.co.id',
            'status' => 'approved',
            'subtotal' => 10000000.00,
            'vat_rate' => 11.00,
            'vat_amount' => 1100000.00,
            'grand_total' => 11100000.00,
            'margin_percentage' => 25.00,
            'currency' => 'IDR',
            'payment_terms' => 'NET 30',
        ]);

        $salesOrder = SalesOrder::create([
            'order_number' => 'PBB/SOR/2026/10/0001',
            'quotation_id' => $quotation->id,
            'status' => 'approved',
            'vat_rate_applied' => 11.00,
            'total_amount' => 11100000.00,
            'currency' => 'IDR',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'PBB/INV/2026/09/0001',
            'sales_order_id' => $salesOrder->id,
            'quotation_id' => $quotation->id,
            'customer_name' => 'Budi Santoso',
            'company_name' => 'PT. Mega Graha Mandiri',
            'company_npwp' => '01.234.567.8-012.000',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 10000000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 1100000.00,
            'grand_total' => 11100000.00,
            'paid_amount' => 5000000.00,
            'currency' => 'IDR',
            'status' => 'partially_paid',
        ]);

        $pdf = PdfExportService::generateInvoicePdf($invoice, false);
        $this->assertNotNull($pdf);

        $output = $pdf->output();
        $this->assertNotEmpty($output);
        $this->assertStringStartsWith('%PDF-', $output);
    }
}
