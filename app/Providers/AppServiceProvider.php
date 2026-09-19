<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Article;
use App\Models\CommissionPayout;
use App\Models\DeliveryOrder;
use App\Models\FreelanceSales;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\UnitOfMeasure;
use App\Models\User;
use App\Observers\ProductObserver;
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
            'user' => User::class,
            'product' => Product::class,
            'inquiry' => Inquiry::class,
            'quotation' => Quotation::class,
            'sales_order' => SalesOrder::class,
            'delivery_order' => DeliveryOrder::class,
            'invoice' => Invoice::class,
            'commission_payout' => CommissionPayout::class,
            'freelance_sales' => FreelanceSales::class,
            'article' => Article::class,
            'unit_of_measure' => UnitOfMeasure::class,
        ]);

        Product::observe(ProductObserver::class);
    }
}
