<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CommissionPayout;
use App\Models\CommissionTier;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DocumentSequence;
use App\Models\FreelanceSales;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\InquiryTelemetry;
use App\Models\InternalComment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSpecification;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationPaymentTerm;
use App\Models\SalesOrder;
use App\Models\SystemSetting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class DomainModelTest extends TestCase
{
    /**
     * Test all 28 models can be instantiated with fillable properties.
     */
    public function test_all_domain_models_can_be_instantiated(): void
    {
        $models = [
            User::class,
            DocumentSequence::class,
            Brand::class,
            Category::class,
            Product::class,
            ProductSpecification::class,
            ProductImage::class,
            CommissionTier::class,
            FreelanceSales::class,
            Inquiry::class,
            InquiryItem::class,
            InquiryTelemetry::class,
            Quotation::class,
            QuotationPaymentTerm::class,
            QuotationItem::class,
            SalesOrder::class,
            DeliveryOrder::class,
            DeliveryOrderItem::class,
            CommissionPayout::class,
            ArticleCategory::class,
            Article::class,
            SystemSetting::class,
            Notification::class,
            InternalComment::class,
            Invoice::class,
            InvoiceItem::class,
            ActivityLog::class,
            UnitOfMeasure::class,
        ];

        $this->assertCount(28, $models);

        foreach ($models as $modelClass) {
            $instance = new $modelClass;
            $this->assertInstanceOf($modelClass, $instance);
            $this->assertIsArray($instance->getFillable());
            $this->assertNotEmpty($instance->getFillable());
        }
    }

    /**
     * Test Quotation margin helper methods per Rule 3.5
     */
    public function test_quotation_margin_helper_methods_evaluate_tiers_correctly(): void
    {
        $quoCritical = new Quotation(['gross_profit_margin' => '5.50']);
        $this->assertTrue($quoCritical->isMarginCritical());
        $this->assertFalse($quoCritical->isMarginWarning());
        $this->assertFalse($quoCritical->isMarginSafe());
        $this->assertEquals('critical', $quoCritical->margin_status);

        $quoWarning = new Quotation(['gross_profit_margin' => '12.00']);
        $this->assertFalse($quoWarning->isMarginCritical());
        $this->assertTrue($quoWarning->isMarginWarning());
        $this->assertFalse($quoWarning->isMarginSafe());
        $this->assertEquals('warning', $quoWarning->margin_status);

        $quoSafe = new Quotation(['gross_profit_margin' => '20.00']);
        $this->assertFalse($quoSafe->isMarginCritical());
        $this->assertFalse($quoSafe->isMarginWarning());
        $this->assertTrue($quoSafe->isMarginSafe());
        $this->assertEquals('safe', $quoSafe->margin_status);
    }
}
