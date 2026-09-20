<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\BulkSkuNormalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkSkuNormalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private Product $samplePump;

    private Product $sampleBreaker;

    protected function setUp(): void
    {
        parent::setUp();

        $brandEbara = Brand::factory()->create(['name' => 'Ebara']);
        $brandSchneider = Brand::factory()->create(['name' => 'Schneider Electric']);
        $category = Category::factory()->create(['name' => 'Pumps & Electrical']);

        $this->samplePump = Product::factory()->create([
            'brand_id' => $brandEbara->id,
            'category_id' => $category->id,
            'name' => 'Pompa Sentrifugal Ebara FSHA 65-50-200',
            'model_number' => 'FSHA 65-50-200',
            'is_published' => true,
        ]);

        $this->sampleBreaker = Product::factory()->create([
            'brand_id' => $brandSchneider->id,
            'category_id' => $category->id,
            'name' => 'Miniature Circuit Breaker Acti9 16A',
            'model_number' => 'SCH-MCB-16A',
            'is_published' => true,
        ]);
    }

    public function test_normalize_standardizes_casing_and_underscores_to_hyphens(): void
    {
        // TC-CAT-IMP-003
        $this->assertSame('SCH-MCB-16A', BulkSkuNormalizationService::normalize(' sch-mcb_16a  '));
        $this->assertSame('ABB-ISOL-32A', BulkSkuNormalizationService::normalize('abb_isol_32a'));
        $this->assertSame('PHI-LED-12W', BulkSkuNormalizationService::normalize('  phi--led__12w  '));
        $this->assertSame('', BulkSkuNormalizationService::normalize('   '));
    }

    public function test_clean_for_comparison_strips_all_non_alphanumerics(): void
    {
        $this->assertSame('FSHA655020055', BulkSkuNormalizationService::cleanForComparison('FSHA 65-50-200/5.5'));
        $this->assertSame('SCHMCB16A', BulkSkuNormalizationService::cleanForComparison('SCH-MCB-16A'));
        $this->assertSame('E2EX7D1N10', BulkSkuNormalizationService::cleanForComparison('E2E-X7D1-N#10'));
    }

    public function test_sanitize_text_field_neutralizes_formula_injection(): void
    {
        // TC-CAT-IMP-011: Formula Injection Prevention
        $formulaPayload = "=SUM(1+1)*cmd|' /C notepad'!A0";
        $sanitized = BulkSkuNormalizationService::sanitizeTextField($formulaPayload);
        $this->assertSame("'=SUM(1+1)*cmd|' /C notepad'!A0", $sanitized);

        $this->assertSame("'+CMD", BulkSkuNormalizationService::sanitizeTextField('+CMD'));
        $this->assertSame("'@URL", BulkSkuNormalizationService::sanitizeTextField('@URL'));
        $this->assertSame("'-EXEC", BulkSkuNormalizationService::sanitizeTextField('-EXEC'));

        // Angka negatif biasa tidak boleh rusak
        $this->assertSame('-100', BulkSkuNormalizationService::sanitizeTextField('-100'));

        // Teks biasa dan yang sudah ber-escape tidak boleh dimodifikasi berulang
        $this->assertSame('Ebara FSHA Pump', BulkSkuNormalizationService::sanitizeTextField('Ebara FSHA Pump'));
        $this->assertSame("'Already escaped", BulkSkuNormalizationService::sanitizeTextField("'Already escaped"));
        $this->assertSame('', BulkSkuNormalizationService::sanitizeTextField('   '));
    }

    public function test_match_sku_exact_match(): void
    {
        $matched = BulkSkuNormalizationService::matchSku('FSHA 65-50-200');

        $this->assertNotNull($matched);
        $this->assertSame($this->samplePump->id, $matched->id);
    }

    public function test_match_sku_case_insensitive(): void
    {
        $matched = BulkSkuNormalizationService::matchSku('fsha 65-50-200');

        $this->assertNotNull($matched);
        $this->assertSame($this->samplePump->id, $matched->id);
    }

    public function test_match_sku_normalized_underscores(): void
    {
        $matched = BulkSkuNormalizationService::matchSku('sch_mcb_16a');

        $this->assertNotNull($matched);
        $this->assertSame($this->sampleBreaker->id, $matched->id);
    }

    public function test_match_sku_tolerant_alphanumeric(): void
    {
        // Variasi spasi dan tanda hubung (UT-04)
        $matchedSpaces = BulkSkuNormalizationService::matchSku('FSHA  65 50 200');
        $this->assertNotNull($matchedSpaces);
        $this->assertSame($this->samplePump->id, $matchedSpaces->id);

        $matchedNoSpaces = BulkSkuNormalizationService::matchSku('FSHA6550200');
        $this->assertNotNull($matchedNoSpaces);
        $this->assertSame($this->samplePump->id, $matchedNoSpaces->id);
    }

    public function test_match_sku_base_part_before_slash_variation(): void
    {
        // UT-04: FSHA 65-50-200/5.5 me-resolve model FSHA 65-50-200
        $matched = BulkSkuNormalizationService::matchSku('FSHA 65-50-200/5.5');

        $this->assertNotNull($matched);
        $this->assertSame($this->samplePump->id, $matched->id);
    }

    public function test_match_sku_returns_null_when_not_found(): void
    {
        $matched = BulkSkuNormalizationService::matchSku('UNKNOWN-PART-NUMBER-999');

        $this->assertNull($matched);
    }

    public function test_match_many_batch_matching(): void
    {
        $inputs = [
            'FSHA 65-50-200',
            'sch_mcb_16a',
            'TIDAK-ADA-123',
        ];

        $results = BulkSkuNormalizationService::matchMany($inputs);

        $this->assertCount(3, $results);

        $first = $results->firstWhere('input', 'FSHA 65-50-200');
        $this->assertTrue($first['is_matched']);
        $this->assertSame('exact', $first['match_type']);
        $this->assertSame($this->samplePump->id, $first['product']->id);

        $second = $results->firstWhere('input', 'sch_mcb_16a');
        $this->assertTrue($second['is_matched']);
        $this->assertSame('tolerant', $second['match_type']);
        $this->assertSame($this->sampleBreaker->id, $second['product']->id);

        $third = $results->firstWhere('input', 'TIDAK-ADA-123');
        $this->assertFalse($third['is_matched']);
        $this->assertSame('none', $third['match_type']);
        $this->assertNull($third['product']);
    }

    public function test_normalize_cleans_spaces_around_hyphens(): void
    {
        $this->assertSame('SCH-MCB-16A', BulkSkuNormalizationService::normalize('SCH - MCB - 16A'));
        $this->assertSame('FSHA 65-50-200', BulkSkuNormalizationService::normalize('FSHA 65 - 50 - 200'));
    }

    public function test_match_sku_with_brand_id_filter(): void
    {
        // Brand Ebara ID cocok dengan samplePump
        $matched = BulkSkuNormalizationService::matchSku('FSHA 65-50-200', $this->samplePump->brand_id);
        $this->assertNotNull($matched);
        $this->assertSame($this->samplePump->id, $matched->id);

        // Jika filter brand_id adalah brand Schneider, maka model Ebara tidak boleh ditemukan
        $mismatched = BulkSkuNormalizationService::matchSku('FSHA 65-50-200', $this->sampleBreaker->brand_id);
        $this->assertNull($mismatched);
    }
}
