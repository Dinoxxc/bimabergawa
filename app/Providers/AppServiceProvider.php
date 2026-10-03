<?php

declare(strict_types=1);

namespace App\Providers;

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
use App\Models\Supplier;
use App\Models\SystemSetting;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Observers\ProductObserver;
use App\Observers\UnitOfMeasureObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'activity_log' => ActivityLog::class,
            'article' => Article::class,
            'article_category' => ArticleCategory::class,
            'brand' => Brand::class,
            'category' => Category::class,
            'commission_payout' => CommissionPayout::class,
            'commission_tier' => CommissionTier::class,
            'delivery_order' => DeliveryOrder::class,
            'delivery_order_item' => DeliveryOrderItem::class,
            'document_sequence' => DocumentSequence::class,
            'freelance_sales' => FreelanceSales::class,
            'inquiry' => Inquiry::class,
            'inquiry_item' => InquiryItem::class,
            'inquiry_telemetry' => InquiryTelemetry::class,
            'internal_comment' => InternalComment::class,
            'invoice' => Invoice::class,
            'invoice_item' => InvoiceItem::class,
            'notification' => Notification::class,
            'product' => Product::class,
            'product_image' => ProductImage::class,
            'product_specification' => ProductSpecification::class,
            'quotation' => Quotation::class,
            'quotation_item' => QuotationItem::class,
            'quotation_payment_term' => QuotationPaymentTerm::class,
            'sales_order' => SalesOrder::class,
            'supplier' => Supplier::class,
            'system_setting' => SystemSetting::class,
            'unit_of_measure' => UnitOfMeasure::class,
            'user' => User::class,
        ]);

        Product::observe(ProductObserver::class);
        UnitOfMeasure::observe(UnitOfMeasureObserver::class);
    }
}
