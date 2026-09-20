<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\ParseSkuRequestDTO;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\SkuParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkuParserServiceTest extends TestCase
{
    use RefreshDatabase;

    private Product $pumpProduct;

    private Product $breakerProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $brandEbara = Brand::factory()->create(['name' => 'Ebara']);
        $brandSchneider = Brand::factory()->create(['name' => 'Schneider Electric']);
        $category = Category::factory()->create(['name' => 'Industrial Equipment']);

        $this->pumpProduct = Product::factory()->create([
            'brand_id' => $brandEbara->id,
            'category_id' => $category->id,
            'name' => 'Pompa Ebara FSHA 65-50-200',
            'model_number' => 'FSHA 65-50-200',
            'is_published' => true,
        ]);

        $this->breakerProduct = Product::factory()->create([
            'brand_id' => $brandSchneider->id,
            'category_id' => $category->id,
            'name' => 'Schneider MCB 16A Acti9',
            'model_number' => 'SCH-MCB-16A',
            'is_published' => true,
        ]);
    }

    public function test_parse_raw_text_standard_tab_and_space_separated(): void
    {
        // TC-PUB-RFQ-006: SCH-MCB-16A 100 \n PHI-LED-12W 50
        $rawText = "SCH-MCB-16A    100\nPHI-LED-12W 50";

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(2, $items);

        $this->assertSame('SCH-MCB-16A', $items[0]['sku']);
        $this->assertSame('SCH-MCB-16A', $items[0]['normalized_sku']);
        $this->assertSame(100, $items[0]['quantity']);
        $this->assertNull($items[0]['unit_note']);

        $this->assertSame('PHI-LED-12W', $items[1]['sku']);
        $this->assertSame('PHI-LED-12W', $items[1]['normalized_sku']);
        $this->assertSame(50, $items[1]['quantity']);
        $this->assertNull($items[1]['unit_note']);
    }

    public function test_parse_raw_text_comma_and_semicolon_with_notes(): void
    {
        // SDD-05 & UI-UX-03: Format Komma & Semicolon beserta Catatan Teknis
        $rawText = "FSHA 65-50-200, 2, Flange ANSI 150\nATV320U55N4B; 4; 380V 3-Phase\nFC-051P4K0 | 1 | IP20";

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(3, $items);

        $this->assertSame('FSHA 65-50-200', $items[0]['sku']);
        $this->assertSame(2, $items[0]['quantity']);
        $this->assertSame('Flange ANSI 150', $items[0]['unit_note']);

        $this->assertSame('ATV320U55N4B', $items[1]['sku']);
        $this->assertSame(4, $items[1]['quantity']);
        $this->assertSame('380V 3-Phase', $items[1]['unit_note']);

        $this->assertSame('FC-051P4K0', $items[2]['sku']);
        $this->assertSame(1, $items[2]['quantity']);
        $this->assertSame('IP20', $items[2]['unit_note']);
    }

    public function test_parse_raw_text_part_number_only_defaults_quantity_to_one(): void
    {
        // TC-PUB-RFQ-007: Input SKU tanpa kuantitas otomatis bernilai 1 pcs
        $rawText = 'ABB-ISOL-32A';

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(1, $items);
        $this->assertSame('ABB-ISOL-32A', $items[0]['sku']);
        $this->assertSame('ABB-ISOL-32A', $items[0]['normalized_sku']);
        $this->assertSame(1, $items[0]['quantity']);
        $this->assertNull($items[0]['unit_note']);
    }

    public function test_parse_raw_text_leading_quantity_and_units(): void
    {
        // Variasi format awalan kuantitas
        $rawText = "2x FSHA 65-50-200\n10 pcs E2E-X7D1-N, Kabel 5M\n5 unit ATV320U55N4B";

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(3, $items);

        $this->assertSame('FSHA 65-50-200', $items[0]['sku']);
        $this->assertSame(2, $items[0]['quantity']);

        $this->assertSame('E2E-X7D1-N', $items[1]['sku']);
        $this->assertSame(10, $items[1]['quantity']);
        $this->assertSame('Kabel 5M', $items[1]['unit_note']);

        $this->assertSame('ATV320U55N4B', $items[2]['sku']);
        $this->assertSame(5, $items[2]['quantity']);
    }

    public function test_parse_raw_text_skips_empty_lines_and_comments(): void
    {
        $rawText = '
# DAFTAR BOQ PENGADAAN MEKANIKAL
// Tanggal: September 2026

SCH-MCB-16A 10


# Catatan tambahan
';

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(1, $items);
        $this->assertSame('SCH-MCB-16A', $items[0]['sku']);
        $this->assertSame(10, $items[0]['quantity']);
    }

    public function test_parse_from_dto_integration(): void
    {
        $dto = new ParseSkuRequestDTO(
            rawContent: "SCH-MCB-16A 25\nFSHA 65-50-200 3",
            delimiter: "\n",
            strictBrandCheck: false
        );

        $items = SkuParserService::parseFromDto($dto);

        $this->assertCount(2, $items);
        $this->assertSame('SCH-MCB-16A', $items[0]['sku']);
        $this->assertSame(25, $items[0]['quantity']);
        $this->assertSame('FSHA 65-50-200', $items[1]['sku']);
        $this->assertSame(3, $items[1]['quantity']);
    }

    public function test_parse_and_match_categorizes_matched_and_unmatched(): void
    {
        $rawText = "FSHA 65-50-200, 2, Spec ANSI\nsch_mcb_16a 5\nPART-TIDAK-DITEMUKAN-999 1";

        $result = SkuParserService::parseAndMatch($rawText);

        $this->assertSame(3, $result['total_lines']);
        $this->assertSame(2, $result['matched_count']);
        $this->assertSame(1, $result['unmatched_count']);

        // Matched items
        $this->assertCount(2, $result['matched']);
        $this->assertSame($this->pumpProduct->id, $result['matched'][0]['product_id']);
        $this->assertSame('exact', $result['matched'][0]['match_type']);
        $this->assertSame(2, $result['matched'][0]['requested_qty']);
        $this->assertSame('Spec ANSI', $result['matched'][0]['unit_note']);

        $this->assertSame($this->breakerProduct->id, $result['matched'][1]['product_id']);
        $this->assertSame('tolerant', $result['matched'][1]['match_type']);
        $this->assertSame(5, $result['matched'][1]['requested_qty']);

        // Unmatched item
        $this->assertCount(1, $result['unmatched']);
        $this->assertSame('PART-TIDAK-DITEMUKAN-999', $result['unmatched'][0]['sku']);
        $this->assertSame(1, $result['unmatched'][0]['requested_qty']);
        $this->assertSame('SKU tidak ditemukan di katalog', $result['unmatched'][0]['reason']);
    }

    public function test_parse_raw_text_malformed_lines_and_pure_symbols_safely_ignored(): void
    {
        $rawText = ",,,;;;|||\n   \n   ,   \nSCH-MCB-16A 1\n;;;;;\n";

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(1, $items);
        $this->assertSame('SCH-MCB-16A', $items[0]['sku']);
        $this->assertSame(1, $items[0]['quantity']);
    }

    public function test_parse_raw_text_zero_or_negative_quantity_clamps_to_one(): void
    {
        $rawText = "SCH-MCB-16A 0\nATV320U55N4B, -5, IP20";

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(2, $items);
        $this->assertSame(1, $items[0]['quantity']);
        $this->assertSame(1, $items[1]['quantity']);
    }

    public function test_parse_raw_text_multiple_commas_in_specification_notes(): void
    {
        $rawText = 'FSHA 65-50-200, 2, Flange ANSI 150, 380V, IP55';

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(1, $items);
        $this->assertSame('FSHA 65-50-200', $items[0]['sku']);
        $this->assertSame(2, $items[0]['quantity']);
        $this->assertSame('Flange ANSI 150, 380V, IP55', $items[0]['unit_note']);
    }

    public function test_parse_raw_text_windows_crlf_and_mixed_newlines(): void
    {
        $rawText = "SCH-MCB-16A 10\r\nPHI-LED-12W 20\nFSHA 65-50-200 5";

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(3, $items);
        $this->assertSame('SCH-MCB-16A', $items[0]['sku']);
        $this->assertSame('PHI-LED-12W', $items[1]['sku']);
        $this->assertSame('FSHA 65-50-200', $items[2]['sku']);
    }

    public function test_parse_and_match_from_dto_integration(): void
    {
        $dto = new ParseSkuRequestDTO(
            rawContent: "SCH-MCB-16A 15\nFSHA 65-50-200 2",
            delimiter: "\n",
            strictBrandCheck: false
        );

        $result = SkuParserService::parseAndMatchFromDto($dto);

        $this->assertSame(2, $result['total_lines']);
        $this->assertSame(2, $result['matched_count']);
        $this->assertSame(0, $result['unmatched_count']);
        $this->assertSame($this->breakerProduct->id, $result['matched'][0]['product_id']);
        $this->assertSame(15, $result['matched'][0]['requested_qty']);
        $this->assertSame($this->pumpProduct->id, $result['matched'][1]['product_id']);
        $this->assertSame(2, $result['matched'][1]['requested_qty']);
    }

    public function test_parse_and_match_with_brand_id_filter(): void
    {
        // Filter hanya brand Ebara
        $rawText = "FSHA 65-50-200 2\nSCH-MCB-16A 10";
        $result = SkuParserService::parseAndMatch($rawText, "\n", $this->pumpProduct->brand_id);

        $this->assertSame(2, $result['total_lines']);
        $this->assertSame(1, $result['matched_count']);
        $this->assertSame(1, $result['unmatched_count']);

        $this->assertSame($this->pumpProduct->id, $result['matched'][0]['product_id']);
        $this->assertSame('SCH-MCB-16A', $result['unmatched'][0]['sku']);
    }

    public function test_parse_and_match_fallback_whole_line_for_spaced_sku_with_trailing_digits(): void
    {
        // Buat produk dengan model_number "3M 6200" (spasi di tengah dan angka di akhir)
        $brand3M = Brand::factory()->create(['name' => '3M']);
        $category = Category::factory()->create(['name' => 'Safety K3']);
        $maskProduct = Product::factory()->create([
            'brand_id' => $brand3M->id,
            'category_id' => $category->id,
            'name' => '3M Half Facepiece Respirator 6200',
            'model_number' => '3M 6200',
            'is_published' => true,
        ]);

        // Input part number "3M 6200" tanpa kuantitas tambahan
        $result = SkuParserService::parseAndMatch('3M 6200');

        $this->assertSame(1, $result['matched_count']);
        $this->assertSame($maskProduct->id, $result['matched'][0]['product_id']);
        $this->assertSame(1, $result['matched'][0]['requested_qty']);
        $this->assertSame('3M 6200', $result['matched'][0]['sku']);
    }

    public function test_parse_raw_text_hyphen_or_colon_separated_quantities(): void
    {
        $rawText = "SCH-MCB-16A - 100\nPHI-LED-12W: 50";

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(2, $items);
        $this->assertSame('SCH-MCB-16A', $items[0]['sku']);
        $this->assertSame(100, $items[0]['quantity']);

        $this->assertSame('PHI-LED-12W', $items[1]['sku']);
        $this->assertSame(50, $items[1]['quantity']);
    }

    public function test_parse_raw_text_leading_qty_without_space(): void
    {
        $rawText = "2xFSHA 65-50-200\n5pcs SCH-MCB-16A";

        $items = SkuParserService::parseRawText($rawText);

        $this->assertCount(2, $items);
        $this->assertSame('FSHA 65-50-200', $items[0]['sku']);
        $this->assertSame(2, $items[0]['quantity']);

        $this->assertSame('SCH-MCB-16A', $items[1]['sku']);
        $this->assertSame(5, $items[1]['quantity']);
    }
}
