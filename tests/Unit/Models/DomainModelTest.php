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
use App\Models\User;
use PHPUnit\Framework\TestCase;

class DomainModelTest extends TestCase
{
    /**
     * Test all 27 models can be instantiated with fillable properties.
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
        ];

        $this->assertCount(27, $models);

        foreach ($models as $modelClass) {
            $instance = new $modelClass;
            $this->assertInstanceOf($modelClass, $instance);
            $this->assertIsArray($instance->getFillable());
            $this->assertNotEmpty($instance->getFillable());
        }
    }
}
