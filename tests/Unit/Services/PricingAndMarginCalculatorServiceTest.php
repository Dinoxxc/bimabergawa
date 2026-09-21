<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\CalculateMarginDTO;
use App\Events\QuotationCalculated;
use App\Exceptions\CriticalMarginException;
use App\Models\SystemSetting;
use App\Services\CommercialCalculationService;
use App\Services\PricingAndMarginCalculatorService;
use App\Services\QuotationCalculationService;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class PricingAndMarginCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed pengaturan dasar komersial untuk pengujian
        SystemSetting::set('commercial_tax.tax_vat_rate', '11.00', 'commercial_tax', 'decimal');
        SystemSetting::set('commercial_tax.critical_margin_threshold', '8.00', 'commercial_tax', 'decimal');
        SystemSetting::set('commercial_tax.warning_margin_threshold', '15.00', 'commercial_tax', 'decimal');
        SystemSetting::set('commercial_tax.director_approval_limit', '100000000.00', 'commercial_tax', 'decimal');
    }

    public function test_safe_margin_calculation_without_director_approval(): void
    {
        $items = [
            [
                'quantity' => 10,
                'unit_price' => 125000.0,
                'cost_price' => 100000.0,
            ],
        ];

        $result = PricingAndMarginCalculatorService::calculate(
            items: $items,
            discountAmount: 0.0,
            commissionRate: 2.5
        );

        $this->assertSame(1250000.0, $result['subtotal']);
        $this->assertSame(1250000.0, $result['dpp']);
        $this->assertSame(1000000.0, $result['total_cost']);
        $this->assertSame(250000.0, $result['gross_profit']);
        $this->assertSame(20.0, $result['margin_percentage']);
        $this->assertSame('safe', $result['margin_tier']);
        $this->assertFalse($result['requires_director_approval']);
        $this->assertSame(11.0, $result['vat_rate_applied']);
        $this->assertSame(137500.0, $result['tax_amount']);
        $this->assertSame(1387500.0, $result['grand_total']);
        $this->assertSame(31250.0, $result['commission_amount']);
    }

    public function test_warning_margin_calculation_requires_director_approval(): void
    {
        $items = [
            [
                'quantity' => 10,
                'unit_price' => 110000.0,
                'cost_price' => 100000.0,
            ],
        ];

        $result = PricingAndMarginCalculatorService::calculate(items: $items);

        $this->assertSame(1100000.0, $result['subtotal']);
        $this->assertSame(1000000.0, $result['total_cost']);
        $this->assertSame(100000.0, $result['gross_profit']);
        $this->assertSame(9.09, $result['margin_percentage']);
        $this->assertSame('warning', $result['margin_tier']);
        $this->assertTrue($result['requires_director_approval']);
    }

    public function test_critical_margin_calculation_requires_director_approval(): void
    {
        $items = [
            [
                'quantity' => 10,
                'unit_price' => 105000.0,
                'cost_price' => 100000.0,
            ],
        ];

        $result = PricingAndMarginCalculatorService::calculate(items: $items);

        $this->assertSame(1050000.0, $result['subtotal']);
        $this->assertSame(1000000.0, $result['total_cost']);
        $this->assertSame(50000.0, $result['gross_profit']);
        $this->assertSame(4.76, $result['margin_percentage']);
        $this->assertSame('critical', $result['margin_tier']);
        $this->assertTrue($result['requires_director_approval']);
    }

    public function test_selling_price_below_cost_is_critical_and_requires_approval(): void
    {
        $items = [
            [
                'quantity' => 5,
                'unit_price' => 90000.0,
                'cost_price' => 100000.0,
            ],
        ];

        $result = PricingAndMarginCalculatorService::calculate(items: $items);

        $this->assertSame(450000.0, $result['subtotal']);
        $this->assertSame(500000.0, $result['total_cost']);
        $this->assertSame(-50000.0, $result['gross_profit']);
        $this->assertSame(-11.11, $result['margin_percentage']);
        $this->assertSame('critical', $result['margin_tier']);
        $this->assertTrue($result['requires_director_approval']);
        $this->assertTrue($result['items'][0]['is_below_cost']);
    }

    public function test_strict_mode_throws_critical_margin_exception(): void
    {
        $items = [
            [
                'quantity' => 1,
                'unit_price' => 105000.0,
                'cost_price' => 100000.0,
            ],
        ];

        $this->expectException(CriticalMarginException::class);
        $this->expectExceptionCode(422);

        PricingAndMarginCalculatorService::calculate(
            items: $items,
            strict: true
        );
    }

    public function test_director_approval_triggered_by_discount_exceeding_ten_percent(): void
    {
        $items = [
            [
                'quantity' => 10,
                'unit_price' => 200000.0,
                'cost_price' => 100000.0,
            ],
        ];

        // Subtotal = 2,000,000. Diskon = 300,000 (15% > 10%)
        $result = PricingAndMarginCalculatorService::calculate(
            items: $items,
            discountAmount: 300000.0
        );

        $this->assertSame(2000000.0, $result['subtotal']);
        $this->assertSame(1700000.0, $result['dpp']);
        // Margin = (1,700,000 - 1,000,000) / 1,700,000 = 41.18% (Safe)
        $this->assertSame('safe', $result['margin_tier']);
        // Tapi approval = true karena diskon > 10%
        $this->assertTrue($result['requires_director_approval']);
    }

    public function test_director_approval_triggered_by_grand_total_exceeding_limit(): void
    {
        $items = [
            [
                'quantity' => 1000,
                'unit_price' => 200000.0,
                'cost_price' => 150000.0,
            ],
        ];

        // Subtotal = 200,000,000. Grand Total = 222,000,000 >= 100 Juta limit
        $result = PricingAndMarginCalculatorService::calculate(items: $items);

        $this->assertSame(200000000.0, $result['subtotal']);
        $this->assertSame(222000000.0, $result['grand_total']);
        $this->assertSame(25.0, $result['margin_percentage']);
        $this->assertSame('safe', $result['margin_tier']);
        // Wajib eskalasi persetujuan Direktur karena nilai transaksi >= Rp 100 Jt
        $this->assertTrue($result['requires_director_approval']);
    }

    public function test_dynamic_vat_snapshot_and_custom_override(): void
    {
        $items = [
            [
                'quantity' => 1,
                'unit_price' => 1000000.0,
                'cost_price' => 800000.0,
            ],
        ];

        // 1. Default VAT dari SystemSetting (11%)
        $defaultResult = PricingAndMarginCalculatorService::calculate(items: $items);
        $this->assertSame(11.0, $defaultResult['vat_rate_applied']);
        $this->assertSame(110000.0, $defaultResult['tax_amount']);

        // 2. Simulasi pembaruan tarif PPN nasional ke 12% via SystemSetting
        SystemSettingService::set('commercial_tax.tax_vat_rate', '12.00');
        $updatedResult = PricingAndMarginCalculatorService::calculate(items: $items);
        $this->assertSame(12.0, $updatedResult['vat_rate_applied']);
        $this->assertSame(120000.0, $updatedResult['tax_amount']);

        // 3. Custom VAT override eksplisit (misal 13%)
        $overrideResult = PricingAndMarginCalculatorService::calculate(
            items: $items,
            vatRate: 13.0
        );
        $this->assertSame(13.0, $overrideResult['vat_rate_applied']);
        $this->assertSame(130000.0, $overrideResult['tax_amount']);
    }

    public function test_foreign_currency_hedging_and_base_conversion(): void
    {
        $items = [
            [
                'quantity' => 2,
                'unit_price' => 100.0,
                'cost_price' => 70.0,
            ],
        ];

        $result = PricingAndMarginCalculatorService::calculate(
            items: $items,
            exchangeRate: 16000.0,
            currency: 'USD'
        );

        $this->assertSame('USD', $result['currency']);
        $this->assertSame(16000.0, $result['exchange_rate_applied']);
        $this->assertSame(200.0, $result['subtotal']);
        $this->assertSame(222.0, $result['grand_total']);
        $this->assertSame(3552000.0, $result['grand_total_base']);
        $this->assertSame(3200000.0, $result['dpp_base']);
    }

    public function test_calculate_from_dto_integration(): void
    {
        $dto = new CalculateMarginDTO(
            costPrice: 80000.0,
            unitPrice: 100000.0,
            quantity: 5,
            discountPercent: 5.0,
            vatRate: 11.0,
            exchangeRate: 1.0,
            currency: 'IDR'
        );

        $result = PricingAndMarginCalculatorService::calculateFromDto($dto);

        $this->assertSame(500000.0, $result['subtotal']);
        $this->assertSame(25000.0, $result['discount_amount']);
        $this->assertSame(475000.0, $result['dpp']);
        $this->assertSame(400000.0, $result['total_cost']);
        $this->assertSame(75000.0, $result['gross_profit']);
        $this->assertSame(15.79, $result['margin_percentage']);
        $this->assertSame('safe', $result['margin_tier']);
    }

    public function test_quotation_calculated_event_dispatched_when_id_provided(): void
    {
        Event::fake([QuotationCalculated::class]);

        $items = [
            [
                'quantity' => 1,
                'unit_price' => 500000.0,
                'cost_price' => 400000.0,
            ],
        ];

        PricingAndMarginCalculatorService::calculate(
            items: $items,
            quotationId: 42
        );

        Event::assertDispatched(QuotationCalculated::class, function (QuotationCalculated $event) {
            return $event->quotationId === 42
                && $event->subtotal === 500000.0
                && $event->marginPercent === 20.0
                && $event->vatAmount === 55000.0;
        });
    }

    public function test_validate_payment_milestones_exact_hundred_percent_succeeds(): void
    {
        $validMilestones = [
            ['phase' => 'DP', 'percentage' => 30.0],
            ['phase' => 'Ready to Ship', 'percentage' => 50.0],
            ['phase' => 'Pelunasan', 'percentage' => 20.0],
        ];

        $this->assertTrue(PricingAndMarginCalculatorService::validatePaymentMilestones($validMilestones));
    }

    public function test_validate_payment_milestones_non_hundred_percent_throws_exception(): void
    {
        $invalidMilestones = [
            ['phase' => 'DP', 'percentage' => 30.0],
            ['phase' => 'Pelunasan', 'percentage' => 60.0],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Akumulasi total persentase termin pembayaran wajib tepat 100.00% (Saat ini: 90.00%).');

        PricingAndMarginCalculatorService::validatePaymentMilestones($invalidMilestones);
    }

    public function test_system_setting_service_typed_getters_and_smart_key_fallback(): void
    {
        SystemSetting::set('general.test_string', 'Rasuna Said');
        SystemSetting::set('general.test_int', '42', 'general', 'integer');
        SystemSetting::set('general.test_bool', '1', 'general', 'boolean');
        SystemSetting::set('general.test_json', json_encode(['A', 'B']), 'general', 'json');

        $this->assertSame('Rasuna Said', SystemSettingService::getString('general.test_string'));
        $this->assertSame(42, SystemSettingService::getInt('general.test_int'));
        $this->assertTrue(SystemSettingService::getBool('general.test_bool'));
        $this->assertSame(['A', 'B'], SystemSettingService::getJson('general.test_json'));

        // Smart key fallback: shorthand key tanpa grup
        $this->assertSame(11.0, SystemSettingService::getFloat('tax_vat_rate'));
        $this->assertSame(8.0, SystemSettingService::getFloat('critical_margin_threshold'));
    }

    public function test_quotation_and_commercial_calculation_service_aliases(): void
    {
        // 1. QuotationCalculationService alias (TC-ERP-QUO-03)
        $itemHpp = QuotationCalculationService::calculateHppMargin(
            unitPrice: 150000.0,
            costPrice: 100000.0,
            quantity: 2
        );
        $this->assertSame(33.33, $itemHpp['margin_percentage']);
        $this->assertSame('safe', $itemHpp['margin_tier']);

        // 2. CommercialCalculationService alias (QA-Testing-01 UT-01)
        $ut01Items = [
            ['quantity' => 2, 'unit_price' => 22500000.0, 'cost_price' => 18000000.0, 'discount_percent' => 0],
        ];

        $ut01Result = CommercialCalculationService::calculateQuotationSummary($ut01Items, 0.0, 11.0, 3.0);

        $this->assertSame(45000000.0, $ut01Result['subtotal']);
        $this->assertSame(45000000.0, $ut01Result['dpp']);
        $this->assertSame(4950000.0, $ut01Result['tax_amount']);
        $this->assertSame(49950000.0, $ut01Result['grand_total']);
        $this->assertSame(20.0, $ut01Result['margin_percentage']);
        $this->assertFalse($ut01Result['requires_director_approval']);
        $this->assertSame(1350000.0, $ut01Result['commission_amount']);
    }

    public function test_empty_items_array_returns_safe_zero_values(): void
    {
        $result = PricingAndMarginCalculatorService::calculate(items: []);

        $this->assertSame(0.0, $result['subtotal']);
        $this->assertSame(0.0, $result['dpp']);
        $this->assertSame(0.0, $result['total_cost']);
        $this->assertSame(0.0, $result['gross_profit']);
        $this->assertSame(0.0, $result['margin_percentage']);
        $this->assertSame(0.0, $result['grand_total']);
        $this->assertSame('critical', $result['margin_tier']);
        $this->assertEmpty($result['items']);
    }

    public function test_discount_greater_than_subtotal_clamps_dpp_to_zero(): void
    {
        $items = [
            ['quantity' => 1, 'unit_price' => 100000.0, 'cost_price' => 80000.0],
        ];

        // Diskon 150,000 > Subtotal 100,000
        $result = PricingAndMarginCalculatorService::calculate(
            items: $items,
            discountAmount: 150000.0
        );

        $this->assertSame(100000.0, $result['subtotal']);
        $this->assertSame(0.0, $result['dpp']);
        $this->assertSame(0.0, $result['tax_amount']);
        $this->assertSame(0.0, $result['grand_total']);
        $this->assertSame(-80000.0, $result['gross_profit']);
        $this->assertSame(0.0, $result['margin_percentage']);
        $this->assertSame('critical', $result['margin_tier']);
        $this->assertTrue($result['requires_director_approval']);
    }

    public function test_zero_or_negative_quantity_clamps_to_at_least_one(): void
    {
        $itemZero = PricingAndMarginCalculatorService::calculateItem(
            quantity: 0,
            unitPrice: 100000.0,
            costPrice: 80000.0
        );
        $this->assertSame(1, $itemZero['quantity']);

        $itemNegative = PricingAndMarginCalculatorService::calculateItem(
            quantity: -5,
            unitPrice: 100000.0,
            costPrice: 80000.0
        );
        $this->assertSame(1, $itemNegative['quantity']);
    }

    public function test_zero_unit_price_item_is_below_cost_and_critical(): void
    {
        $item = PricingAndMarginCalculatorService::calculateItem(
            quantity: 2,
            unitPrice: 0.0,
            costPrice: 50000.0
        );

        $this->assertSame(0.0, $item['subtotal']);
        $this->assertSame(100000.0, $item['total_cost']);
        $this->assertSame(-100000.0, $item['gross_profit']);
        $this->assertTrue($item['is_below_cost']);
        $this->assertSame('critical', $item['margin_tier']);
    }

    public function test_invalid_exchange_rate_and_negative_commission_safely_handled(): void
    {
        $items = [
            ['quantity' => 1, 'unit_price' => 100000.0, 'cost_price' => 80000.0],
        ];

        $result = PricingAndMarginCalculatorService::calculate(
            items: $items,
            commissionRate: -5.0, // Negative commission rate
            exchangeRate: -100.0  // Negative exchange rate
        );

        $this->assertSame(1.0, $result['exchange_rate_applied']);
        $this->assertSame(0.0, $result['commission_amount']);
    }

    public function test_milestone_floating_point_precision_tolerance(): void
    {
        // 33.33% + 33.33% + 33.34% = 100.00%
        $milestones = [
            ['phase' => 'DP', 'percentage' => 33.33],
            ['phase' => 'Progress', 'percentage' => 33.33],
            ['phase' => 'Pelunasan', 'percentage' => 33.34],
        ];

        $this->assertTrue(PricingAndMarginCalculatorService::validatePaymentMilestones($milestones));
    }

    public function test_negative_unit_price_or_cost_price_is_safely_clamped_to_zero(): void
    {
        $item = PricingAndMarginCalculatorService::calculateItem(
            quantity: 2,
            unitPrice: -50000.0,
            costPrice: -25000.0
        );

        $this->assertSame(0.0, $item['unit_price']);
        $this->assertSame(0.0, $item['cost_price']);
        $this->assertSame(0.0, $item['subtotal']);
        $this->assertSame(0.0, $item['total_cost']);
        $this->assertSame(0.0, $item['gross_profit']);
        $this->assertSame(0.0, $item['margin_percentage']);
    }

    public function test_item_discount_amount_exceeding_item_gross_is_capped_at_item_gross(): void
    {
        // Gross = 100,000, Diskon = 150,000 -> Capped di 100,000
        $item = PricingAndMarginCalculatorService::calculateItem(
            quantity: 1,
            unitPrice: 100000.0,
            costPrice: 80000.0,
            discountAmount: 150000.0
        );

        $this->assertSame(100000.0, $item['discount_amount']);
        $this->assertSame(0.0, $item['net_total']);
        $this->assertSame(-80000.0, $item['gross_profit']);
    }

    public function test_system_setting_model_booted_observer_clears_service_cache_automatically(): void
    {
        // 1. Initial value
        SystemSettingService::set('commercial_tax.test_cache_obs', '10.00');
        $this->assertSame(10.0, SystemSettingService::getFloat('commercial_tax.test_cache_obs'));

        // 2. Direct model update simulating Filament form save
        $setting = SystemSetting::where('key', 'commercial_tax.test_cache_obs')->first();
        $setting->value = '25.00';
        $setting->save();

        // 3. Service cache must immediately reflect the updated value
        $this->assertSame(25.0, SystemSettingService::getFloat('commercial_tax.test_cache_obs'));
        // And via shorthand
        $this->assertSame(25.0, SystemSettingService::getFloat('test_cache_obs'));
    }

    public function test_validate_payment_milestones_empty_array_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Daftar termin pembayaran tidak boleh kosong.');

        PricingAndMarginCalculatorService::validatePaymentMilestones([]);
    }

    public function test_validate_payment_milestones_negative_or_zero_phase_throws_exception(): void
    {
        $invalidMilestones = [
            ['phase' => 'DP', 'percentage' => -50.0],
            ['phase' => 'Pelunasan', 'percentage' => 150.0],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Setiap termin pembayaran wajib memiliki persentase positif lebih dari 0%.');

        PricingAndMarginCalculatorService::validatePaymentMilestones($invalidMilestones);
    }

    public function test_negative_vat_rate_is_safely_clamped_to_zero(): void
    {
        $items = [
            ['quantity' => 1, 'unit_price' => 100000.0, 'cost_price' => 80000.0],
        ];

        $result = PricingAndMarginCalculatorService::calculate(
            items: $items,
            vatRate: -10.0
        );

        $this->assertSame(0.0, $result['vat_rate_applied']);
        $this->assertSame(0.0, $result['tax_amount']);
        $this->assertSame(100000.0, $result['grand_total']);
    }

    public function test_fractional_quantity_is_accurately_calculated_without_truncation(): void
    {
        $items = [
            ['quantity' => 15.5, 'unit_price' => 20000.0, 'cost_price' => 15000.0],
            ['quantity' => 0.75, 'unit_price' => 40000.0, 'cost_price' => 30000.0],
        ];

        $result = PricingAndMarginCalculatorService::calculate(
            items: $items,
            vatRate: 11.0
        );

        // Item 1: 15.5 * 20000 = 310,000 | Cost: 15.5 * 15000 = 232,500
        // Item 2: 0.75 * 40000 = 30,000  | Cost: 0.75 * 30000 = 22,500
        // Subtotal = 340,000 | Total cost = 255,000 | DPP = 340,000 | VAT 11% = 37,400 | Grand total = 377,400
        $this->assertSame(340000.0, $result['subtotal']);
        $this->assertSame(255000.0, $result['total_cost']);
        $this->assertSame(340000.0, $result['dpp']);
        $this->assertSame(37400.0, $result['tax_amount']);
        $this->assertSame(377400.0, $result['grand_total']);
        $this->assertSame(85000.0, $result['gross_profit']);
        $this->assertSame(15.5, $result['items'][0]['quantity']);
        $this->assertSame(0.75, $result['items'][1]['quantity']);
    }
}
