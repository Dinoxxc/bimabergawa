<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\QuotationResource;
use App\Filament\Resources\QuotationResource\Pages\CreateQuotation;
use App\Filament\Resources\QuotationResource\Pages\EditQuotation;
use App\Filament\Resources\QuotationResource\Pages\ViewQuotation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DocumentNumberService;
use App\Services\PdfExportService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuotationResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $salesUser;

    protected User $financeUser;

    protected User $logisticsUser;

    protected Product $mcbProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->salesUser = User::factory()->create([
            'email' => 'sales@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->salesUser->assignRole('sales_pic');

        $this->financeUser = User::factory()->create([
            'email' => 'finance@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->financeUser->assignRole('finance');

        $this->logisticsUser = User::factory()->create([
            'email' => 'logistics@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->logisticsUser->assignRole('logistics');

        $brand = Brand::create([
            'name' => 'Schneider Electric',
            'slug' => 'schneider-electric',
            'is_official_partner' => true,
        ]);

        $category = Category::create([
            'name' => 'Circuit Breaker',
            'slug' => 'circuit-breaker',
        ]);

        $this->mcbProduct = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'MCB Acti9 iC60N 3P 16A 6kA',
            'model_number' => 'A9F74316',
            'slug' => 'mcb-acti9-ic60n-3p-16a-6ka',
            'unit_price' => 125000.00,
            'cost_price' => 100000.00,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);
    }

    /**
     * TC-ERP-QUO-001: Pembuatan Draf Penawaran Harga (Quotation Draft) & Render List
     */
    public function test_super_admin_can_view_quotations_list_and_create_page(): void
    {
        $this->actingAs($this->superAdmin);

        $quotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0001',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Mega Daya Kontraktor',
            'customer_name' => 'Ir. Gunawan',
            'project_name' => 'Panel Fabrication Phase 1',
            'delivery_terms' => 'Loco Warehouse PBB Jakarta',
            'validity_days' => 14,
            'expires_at' => now()->addDays(14)->toDateString(),
            'subtotal' => 1250000.00,
            'discount_amount' => 0.00,
            'dpp' => 1250000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 137500.00,
            'grand_total' => 1387500.00,
            'currency' => 'IDR',
            'total_cost' => 1000000.00,
            'gross_profit_margin' => 20.00,
            'status' => 'draft',
        ]);

        $response = $this->get(QuotationResource::getUrl('index'));
        $response->assertSuccessful();
        $response->assertSee('PBB/QUO/2026/09/0001');
        $response->assertSee('PT. Mega Daya Kontraktor');

        $createResponse = $this->get(QuotationResource::getUrl('create'));
        $createResponse->assertSuccessful();
    }

    /**
     * TC-ERP-QUO-002: Live Margin HPP Ideal (Margin >= 15%)
     */
    public function test_quotation_creation_with_safe_margin(): void
    {
        $this->actingAs($this->salesUser);

        Livewire::test(CreateQuotation::class)
            ->fillForm([
                'company_name' => 'PT. Tri Megah Elektrik',
                'customer_name' => 'Budi Santoso',
                'project_name' => 'Data Center Panel Supply',
                'delivery_terms' => 'Loco Warehouse PBB Jakarta',
                'validity_days' => 14,
                'currency' => 'IDR',
                'items' => [
                    [
                        'product_id' => $this->mcbProduct->id,
                        'item_name' => $this->mcbProduct->name,
                        'model_number' => $this->mcbProduct->model_number,
                        'quantity' => 10,
                        'uom' => 'pcs',
                        'unit_price' => 125000.00,
                        'cost_price' => 100000.00,
                        'discount_percent' => 0,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $quotation = Quotation::where('company_name', 'PT. Tri Megah Elektrik')->first();
        $this->assertNotNull($quotation);
        $this->assertEquals(1250000.00, (float) $quotation->subtotal);
        $this->assertEquals(1000000.00, (float) $quotation->total_cost);
        $this->assertEquals(20.00, (float) $quotation->gross_profit_margin);
        $this->assertEquals('draft', $quotation->status);
        $this->assertFalse((bool) $quotation->is_approved_by_director);
    }

    /**
     * TC-ERP-QUO-004: Blokir & Kewajiban Approval Direksi saat Margin Kritis (< 8%)
     */
    public function test_quotation_with_critical_margin_requires_director_approval(): void
    {
        $this->actingAs($this->salesUser);

        // Cost = 100.000, Unit Price = 105.000 (Margin = 4.76% < 8%)
        Livewire::test(CreateQuotation::class)
            ->fillForm([
                'company_name' => 'PT. Baja Perkasa Indonesia',
                'customer_name' => 'Hendro',
                'project_name' => 'Low Margin Project',
                'delivery_terms' => 'Loco Warehouse PBB Jakarta',
                'validity_days' => 14,
                'currency' => 'IDR',
                'items' => [
                    [
                        'product_id' => $this->mcbProduct->id,
                        'item_name' => $this->mcbProduct->name,
                        'model_number' => $this->mcbProduct->model_number,
                        'quantity' => 10,
                        'uom' => 'pcs',
                        'unit_price' => 105000.00,
                        'cost_price' => 100000.00,
                        'discount_percent' => 0,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $quotation = Quotation::where('company_name', 'PT. Baja Perkasa Indonesia')->first();
        $this->assertNotNull($quotation);
        $this->assertEquals('pending_director_approval', $quotation->status);
        $this->assertFalse((bool) $quotation->is_approved_by_director);
        $this->assertTrue((float) $quotation->gross_profit_margin < 8.0);
    }

    /**
     * TC-ERP-QUO-004 Part 2: Approval Direksi Berhasil
     */
    public function test_director_can_approve_critical_margin_quotation(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0002',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Baja Perkasa Indonesia',
            'customer_name' => 'Hendro',
            'subtotal' => 1050000.00,
            'dpp' => 1050000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 115500.00,
            'grand_total' => 1165500.00,
            'total_cost' => 1000000.00,
            'gross_profit_margin' => 4.76,
            'status' => 'pending_director_approval',
            'is_approved_by_director' => false,
        ]);

        $this->actingAs($this->superAdmin);

        Livewire::test(EditQuotation::class, ['record' => $quotation->id])
            ->callAction('approve_director', [
                'director_notes' => 'Approved exception for strategic long-term key account.',
            ])
            ->assertHasNoActionErrors();

        $quotation->refresh();
        $this->assertEquals('approved', $quotation->status);
        $this->assertTrue((bool) $quotation->is_approved_by_director);
        $this->assertEquals($this->superAdmin->id, $quotation->approved_by);
        $this->assertNotNull($quotation->approved_at);
        $this->assertEquals('Approved exception for strategic long-term key account.', $quotation->director_notes);
    }

    /**
     * TC-ERP-QUO-005: Penerapan Snapshot Pajak PPN 11% Statis
     */
    public function test_quotation_snapshots_vat_rate_permanently(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'commercial_tax.tax_vat_rate'],
            ['group' => 'commercial_tax', 'value' => '11.00', 'type' => 'decimal']
        );

        $quotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0003',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Cipta Karya Lestari',
            'customer_name' => 'Anton',
            'subtotal' => 1000000.00,
            'dpp' => 1000000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 110000.00,
            'grand_total' => 1110000.00,
            'status' => 'approved',
        ]);

        // Simulasikan kenaikan tarif PPN nasional ke 12%
        SystemSetting::updateOrCreate(
            ['key' => 'commercial_tax.tax_vat_rate'],
            ['group' => 'commercial_tax', 'value' => '12.00', 'type' => 'decimal']
        );

        $quotation->refresh();
        $this->assertEquals(11.00, (float) $quotation->vat_rate_applied);
        $this->assertEquals(110000.00, (float) $quotation->tax_amount);
    }

    /**
     * TC-ERP-QUO-006: Pembuatan Berkas PDF Resmi Penawaran Harga (DomPDF Export)
     */
    public function test_quotation_pdf_export_generates_valid_pdf_document(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0004',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Sinar Jaya Mandiri',
            'customer_name' => 'Rina Wijaya',
            'project_name' => 'Lighting & Switchgear Refurbishment',
            'delivery_terms' => 'Loco Warehouse PBB Jakarta',
            'validity_days' => 14,
            'subtotal' => 2500000.00,
            'dpp' => 2500000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 275000.00,
            'grand_total' => 2775000.00,
            'currency' => 'IDR',
            'status' => 'approved',
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'product_id' => $this->mcbProduct->id,
            'item_name' => $this->mcbProduct->name,
            'model_number' => $this->mcbProduct->model_number,
            'quantity' => 20,
            'uom' => 'pcs',
            'unit_price' => 125000.00,
            'cost_price' => 100000.00,
            'discount_percent' => 0.00,
            'total_price' => 2500000.00,
        ]);

        $pdf = PdfExportService::generateQuotationPdf($quotation, saveToStorage: true);
        $binary = $pdf->output();

        $this->assertNotEmpty($binary);
        // Validasi header signature PDF
        $this->assertStringStartsWith('%PDF-', $binary);
        $this->assertNotNull($quotation->pdf_path);
    }

    /**
     * TC-ERP-QUO-008: Alur Pembuatan Revisi Dokumen Penawaran (Revision Tracking)
     */
    public function test_quotation_revision_flow_creates_r1_and_supersedes_original(): void
    {
        $this->actingAs($this->superAdmin);

        $quotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0005',
            'revision_number' => 0,
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Sentosa Abadi Teknik',
            'customer_name' => 'Wahyudi',
            'subtotal' => 1000000.00,
            'dpp' => 1000000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 110000.00,
            'grand_total' => 1110000.00,
            'status' => 'approved',
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'item_name' => 'Contactor LC1D09M7',
            'model_number' => 'LC1D09M7',
            'quantity' => 5,
            'unit_price' => 200000.00,
            'cost_price' => 160000.00,
            'total_price' => 1000000.00,
        ]);

        Livewire::test(ViewQuotation::class, ['record' => $quotation->id])
            ->callAction('create_revision');

        $quotation->refresh();
        $this->assertEquals('superseded', $quotation->status);

        $revisedQuotation = Quotation::where('quotation_number', 'PBB/QUO/2026/09/0005-R1')->first();
        $this->assertNotNull($revisedQuotation);
        $this->assertEquals(1, $revisedQuotation->revision_number);
        $this->assertEquals('draft', $revisedQuotation->status);
        $this->assertCount(1, $revisedQuotation->items);
    }

    /**
     * TC-ERP-QUO-011: Modifikasi Item pada Quotation yang Telah Berstatus Approved/Won
     */
    public function test_approved_quotation_is_locked_from_editing(): void
    {
        $approvedQuotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0006',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Karya Logam Makmur',
            'customer_name' => 'Bambang',
            'subtotal' => 500000.00,
            'dpp' => 500000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 55000.00,
            'grand_total' => 555000.00,
            'status' => 'approved',
        ]);

        $this->assertFalse(QuotationResource::canEdit($approvedQuotation));

        $draftQuotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0007',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Draft Corporation',
            'customer_name' => 'Agus',
            'subtotal' => 500000.00,
            'status' => 'draft',
        ]);

        $this->assertTrue(QuotationResource::canEdit($draftQuotation));
    }

    /**
     * TC-ERP-QUO-012: Penyembunyian Angka HPP dan Persentase Margin dari Serialisasi
     */
    public function test_hidden_attributes_protect_cost_price_and_margin_serialization(): void
    {
        $quotation = Quotation::create([
            'quotation_number' => 'PBB/QUO/2026/09/0008',
            'user_id' => $this->salesUser->id,
            'company_name' => 'PT. Rahasia Modal HPP',
            'customer_name' => 'Klien',
            'subtotal' => 1250000.00,
            'dpp' => 1250000.00,
            'vat_rate_applied' => 11.00,
            'tax_amount' => 137500.00,
            'grand_total' => 1387500.00,
            'total_cost' => 1000000.00,
            'gross_profit_margin' => 20.00,
            'director_notes' => 'Catatan internal direksi tidak boleh bocor',
            'status' => 'approved',
        ]);

        $item = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'item_name' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 1250000.00,
            'cost_price' => 1000000.00,
            'total_price' => 1250000.00,
        ]);

        $quotationArray = $quotation->toArray();
        $this->assertArrayNotHasKey('total_cost', $quotationArray);
        $this->assertArrayNotHasKey('gross_profit_margin', $quotationArray);
        $this->assertArrayNotHasKey('director_notes', $quotationArray);

        $itemArray = $item->toArray();
        $this->assertArrayNotHasKey('cost_price', $itemArray);
    }

    /**
     * TC-ERP-QUO-016: Penomoran Dokumen Penawaran Simultan (Atomic Sequence)
     */
    public function test_document_number_service_generates_unique_quotation_numbers(): void
    {
        $num1 = DocumentNumberService::generateNext('QUO');
        $num2 = DocumentNumberService::generateNext('QUO');

        $this->assertStringStartsWith('PBB/QUO/', $num1);
        $this->assertStringStartsWith('PBB/QUO/', $num2);
        $this->assertNotEquals($num1, $num2);
    }
}
