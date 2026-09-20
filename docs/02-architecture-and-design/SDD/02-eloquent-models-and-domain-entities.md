# 02. Model Eloquent & Entitas Domain
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-02` |
| **Versi Dokumen** | 1.9.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Senior Backend Engineer | Draf Awal | Definisi 14 Model Eloquent, casts, fillable attributes, & relasi ORM |
| `1.1.0` | 03 Sep 2026 | Application Architect | Ditinjau | Penambahan Eloquent Global Scope `FreelanceScope` untuk isolasi tenant agen |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Finalisasi relasi `Quotation` -> `QuotationPaymentTerm` & helper accessor margin |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |
| `1.5.0` | 08 Sep 2026 | Principal System Architect | Disetujui | Penambahan trait `SoftDeletes` pada entitas master/transaksi, atribut `currency`, `customer_po_file_path`, `director_notes`, serta Model `Invoice`, `InvoiceItem`, dan `ActivityLog` |
| `1.5.1` | 08 Sep 2026 | Principal System Architect | Disetujui | Penambahan atribut `paid_amount`, accessor `remaining_balance` pada Model `Invoice`, serta panduan Enforce Morph Map |
| `1.6.0` | 08 Sep 2026 | Principal System Architect | Disetujui | Ekspansi komprehensif seluruh 27 Model Eloquent domain, kelas `AgentTenantScope`, `ProductObserver` untuk soft-deletes unique slug, dan konfigurasi `Relation::enforceMorphMap()` |
| `1.6.1` | 08 Sep 2026 | Principal System Architect | Disetujui | Penyempurnaan relasi withTrashed() pada child item transaksi historis, Inquiry latestOfMany(), dan lifecycle hook restoring() pada ProductObserver |
| `1.7.0` | 08 Sep 2026 | Principal System Architect | Disetujui | Penyempurnaan mitigasi 5 celah arsitektur: loop deterministik restoring slug, AgentTenantScope bypass internal & scoping CommissionPayout, global scope agent_comment_visibility pada InternalComment, full-chain withTrashed() transaksi historis, dan helper margin status Quotation |
| `1.8.0` | 19 Sep 2026 | Principal System Architect | Disetujui | Penambahan Model `UnitOfMeasure` (Master Satuan Dinamis), ekspansi 28 Model Domain, integrasi atribut `uom`, `project_name`, `company_npwp`, `faktur_pajak_number`, `driver_name`, `vehicle_plate_number`, `moq`, `warranty_period`, serta kustomisasi spesifikasi dinamis per item (`custom_specifications`) |
| `1.9.0` | 20 Sep 2026 | Principal System Architect | Disetujui | Integrasi model Tahap A: `exchange_rate_applied`, `base_currency`, `idempotency_key` (Quotation/SO/Payout), `spec_template` (Category), `UnitOfMeasureObserver`, dan full-chain withTrashed() User->freelanceSales() |

---

## 1. Konvensi & Standar Model Eloquent

Seluruh model domain ditempatkan di direktori `app/Models/`, mewarisi `Illuminate\Database\Eloquent\Model`, diawali dengan deklarasi strict types `declare(strict_types=1);`, serta memanfaatkan fitur modern Laravel 11:

- **Mass Assignment:** Menggunakan array `$fillable` eksplisit untuk seluruh kolom input formulir guna mencegah eksploitasi *mass assignment vulnerability*.
- **Tipe Data Casts:** Menggunakan method `casts(): array` modern untuk casting desimal (`decimal:2`), boolean, datetime, date, array/json, dan enkripsi data sensitif (`'encrypted'`).
- **Integritas Relasi:** Menggunakan type-hinting resmi (`BelongsTo`, `HasMany`, `HasOne`, `MorphTo`, `MorphMany`).
- **Proteksi Data Fisik (SoftDeletes):** Model master dan transaksi penting (`Product`, `Inquiry`, `Quotation`, `SalesOrder`, `DeliveryOrder`, `FreelanceSales`, `Invoice`) menerapkan trait `SoftDeletes` untuk menjamin kepatuhan audit retensi 10 tahun UU KUP.
- **Enkripsi At-Rest (UU PDP No. 27/2022):** Model `FreelanceSales` mengenkripsi kolom identitas (`nik`, `npwp_number`, `bank_account_number`) menggunakan kunci simetris `APP_KEY`.
- **Isolasi Tenant Otomatis & Anti-IDOR:** Model `Inquiry`, `Quotation`, dan `CommissionPayout` mengimplementasikan Global Scope `AgentTenantScope` dengan perlindungan bypass eksplisit untuk seluruh peran staf internal (`super_admin`, `sales_pic`, `finance`, `logistics`).
- **Perlindungan Catatan Internal:** Model `InternalComment` menerapkan Global Scope `agent_comment_visibility` otomatis sehingga akun agen freelance tidak dapat membaca catatan internal, modal HPP, atau instruksi direksi.
- **Integritas Rantai Transaksi Historis (Full-Chain withTrashed):** Seluruh relasi `BelongsTo` yang mengarah pada entitas SoftDeletable (`Product`, `Inquiry`, `Quotation`, `SalesOrder`, `DeliveryOrder`, `Invoice`, `FreelanceSales`) wajib menyertakan `->withTrashed()` guna menjamin kelangsungan audit 10 tahun UU KUP dan penerbitan dokumen historis.

---

## 2. Definisi 28 Model Domain & Relasi Bisnis

### 2.1 Model `Product` (Master SKU Multi-Brand)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'brand_id', 'category_id', 'successor_product_id', 'name', 'slug',
        'model_number', 'cost_price', 'currency', 'description', 'datasheet_path',
        'availability_status', 'is_featured', 'is_published', 'meta_description'
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function successor(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'successor_product_id')->withTrashed();
    }

    public function predecessors(): HasMany
    {
        return $this->hasMany(Product::class, 'successor_product_id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
```

### 2.2 Model `Brand` (Prinsipal Global)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Brand extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo_path', 'website_url',
        'description', 'is_active', 'sort_order'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

### 2.3 Model `Category` (Hierarki Taksonomi 2-Tingkat)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'icon_name',
        'description', 'sort_order'
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeRoot(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
```

### 2.4 Model `ProductSpecification` & `ProductImage`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecification extends Model
{
    protected $fillable = ['product_id', 'spec_key', 'spec_value', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_path', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
```

### 2.5 Model `Inquiry`, `InquiryItem` & `InquiryTelemetry`
```php
namespace App\Models;

use App\Models\Scopes\AgentTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class Inquiry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'inquiry_number', 'assigned_user_id', 'freelance_sales_id',
        'customer_name', 'company_name', 'phone_wa', 'email',
        'project_location', 'type', 'source', 'status', 'notes',
        'boq_attachment_path', 'first_contacted_at'
    ];

    protected function casts(): array
    {
        return [
            'first_contacted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new AgentTenantScope());
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class);
    }

    public function telemetry(): HasOne
    {
        return $this->hasOne(InquiryTelemetry::class);
    }

    public function quotation(): HasOne
    {
        return $this->hasOne(Quotation::class)->latestOfMany();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function scopeStale(Builder $query): Builder
    {
        return $query->where('status', 'new')
            ->where('created_at', '<=', now()->subHours(2));
    }
}

class InquiryItem extends Model
{
    protected $fillable = [
        'inquiry_id', 'product_id', 'item_name',
        'model_number', 'quantity', 'custom_notes'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}

class InquiryTelemetry extends Model
{
    protected $fillable = [
        'inquiry_id', 'utm_source', 'utm_medium', 'utm_campaign',
        'utm_term', 'utm_content', 'referrer_url', 'landing_page',
        'device_type', 'ip_address'
    ];

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class)->withTrashed();
    }
}
```

### 2.6 Model `Quotation`, `QuotationPaymentTerm` & `QuotationItem`
```php
namespace App\Models;

use App\Models\Scopes\AgentTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quotation_number', 'revision_number', 'inquiry_id', 'user_id',
        'freelance_sales_id', 'customer_name', 'company_name',
        'delivery_terms', 'validity_days', 'expires_at', 'subtotal',
        'discount_amount', 'dpp', 'vat_rate_applied', 'tax_amount',
        'grand_total', 'currency', 'total_cost', 'gross_profit_margin',
        'is_approved_by_director', 'status', 'pdf_path', 'approved_by',
        'approved_at', 'director_notes'
    ];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'validity_days' => 'integer',
            'expires_at' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'dpp' => 'decimal:2',
            'vat_rate_applied' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'gross_profit_margin' => 'decimal:2',
            'is_approved_by_director' => 'boolean',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new AgentTenantScope());
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function paymentTerms(): HasMany
    {
        return $this->hasMany(QuotationPaymentTerm::class)->orderBy('sort_order');
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }

    public function scopeExpiringInDays(Builder $query, int $days = 3): Builder
    {
        return $query->whereIn('status', ['sent', 'approved'])
            ->whereDate('expires_at', '=', now()->addDays($days)->toDateString());
    }
}

class QuotationPaymentTerm extends Model
{
    protected $fillable = [
        'quotation_id', 'phase_name', 'percentage', 'amount', 'sort_order'
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class)->withTrashed();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'payment_term_id');
    }
}

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'product_id', 'item_name', 'model_number',
        'lead_time', 'quantity', 'unit_price', 'cost_price',
        'discount_percent', 'total_price'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
```

### 2.7 Model `SalesOrder` (Pesanan Penjualan Terikat PO)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'quotation_id', 'customer_po_number',
        'customer_po_date', 'customer_po_file_path', 'status',
        'vat_rate_applied', 'total_amount', 'currency',
        'is_approved_by_director', 'approved_by', 'approved_at'
    ];

    protected function casts(): array
    {
        return [
            'customer_po_date' => 'date',
            'vat_rate_applied' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'is_approved_by_director' => 'boolean',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function commissionPayout(): HasOne
    {
        return $this->hasOne(CommissionPayout::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
```

### 2.8 Model `DeliveryOrder` & `DeliveryOrderItem` (Surat Jalan Logistik)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DeliveryOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'delivery_number', 'sales_order_id', 'courier_name',
        'tracking_number', 'tracking_url', 'shipping_address',
        'recipient_pic_name', 'recipient_phone', 'total_packages',
        'total_weight_kg', 'status', 'proof_of_delivery_path', 'shipped_at'
    ];

    protected function casts(): array
    {
        return [
            'total_packages' => 'integer',
            'total_weight_kg' => 'decimal:2',
            'shipped_at' => 'datetime',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}

class DeliveryOrderItem extends Model
{
    protected $fillable = [
        'delivery_order_id', 'product_id', 'item_name',
        'model_number', 'quantity_shipped'
    ];

    protected function casts(): array
    {
        return [
            'quantity_shipped' => 'integer',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
```

### 2.9 Model `Invoice` & `InvoiceItem` (Penagihan Komersial B2B)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'sales_order_id', 'quotation_id', 'payment_term_id',
        'customer_name', 'company_name', 'invoice_date', 'due_date',
        'subtotal', 'vat_rate_applied', 'tax_amount', 'grand_total',
        'paid_amount', 'currency', 'status', 'payment_method',
        'payment_proof_path', 'paid_at', 'notes', 'pdf_path'
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_rate_applied' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function getRemainingBalanceAttribute(): float
    {
        return (float) max(0, $this->grand_total - $this->paid_amount);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class)->withTrashed();
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class)->withTrashed();
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(QuotationPaymentTerm::class, 'payment_term_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'item_name',
        'model_number', 'quantity', 'unit_price', 'total_price'
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
```

### 2.10 Model `FreelanceSales`, `CommissionTier` & `CommissionPayout`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FreelanceSales extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'commission_tier_id', 'user_id', 'name', 'referral_code', 'nik',
        'npwp_number', 'ktp_file_path', 'bank_name', 'bank_account_number',
        'bank_account_name', 'industry_focus', 'status', 'rejection_reason', 'approved_at'
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'npwp_number' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'approved_at' => 'datetime',
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(CommissionTier::class, 'commission_tier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(CommissionPayout::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}

class CommissionTier extends Model
{
    protected $fillable = [
        'name', 'min_turnover_amount', 'commission_rate', 'is_active'
    ];

    protected function casts(): array
    {
        return [
            'min_turnover_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function freelanceSales(): HasMany
    {
        return $this->hasMany(FreelanceSales::class);
    }
}

class CommissionPayout extends Model
{
    protected $fillable = [
        'payout_number', 'freelance_sales_id', 'sales_order_id',
        'amount', 'type', 'status', 'transfer_proof_path',
        'notes', 'paid_at'
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class)->withTrashed();
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
```

### 2.11 Model `ArticleCategory` & `Article` (Website CMS Insights)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ArticleCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}

class Article extends Model
{
    protected $fillable = [
        'article_category_id', 'author_id', 'title', 'slug',
        'excerpt', 'content', 'featured_image_path', 'reading_time_minutes',
        'is_published', 'published_at', 'meta_description'
    ];

    protected function casts(): array
    {
        return [
            'reading_time_minutes' => 'integer',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
```

### 2.12 Model `SystemSetting` (Konfigurasi Global Dinamis)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'type', 'description', 'is_public'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value, ?string $group = null, ?string $type = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            array_filter(['value' => (string) $value, 'group' => $group, 'type' => $type])
        );
        Cache::forget("system_setting_{$key}");
    }
}
```

### 2.13 Model `Notification` & `InternalComment`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    protected $fillable = ['type', 'notifiable_type', 'notifiable_id', 'data', 'read_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}

class InternalComment extends Model
{
    protected $fillable = [
        'user_id', 'commentable_type', 'commentable_id',
        'comment', 'type', 'mentioned_user_ids', 'attachment_path'
    ];

    protected function casts(): array
    {
        return [
            'mentioned_user_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeAgentVisible(Builder $query): Builder
    {
        return $query->where('type', 'agent_visible');
    }

    public function scopeInternalOnly(Builder $query): Builder
    {
        return $query->where('type', 'internal');
    }
}
```

### 2.14 Model `ActivityLog` (Audit Trail Forensik Imutabel)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'log_name', 'description', 'subject_type', 'subject_id',
        'causer_type', 'causer_id', 'properties', 'ip_address',
        'user_agent', 'created_at'
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public static function log(
        string $logName,
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = []
    ): self {
        return static::create([
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'causer_type' => $causer ? $causer->getMorphClass() : null,
            'causer_id' => $causer ? $causer->getKey() : null,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
```

### 2.15 Model `DocumentSequence` (Sequence Generator Concurrency Lock)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = ['document_type', 'year_month', 'current_sequence'];

    protected function casts(): array
    {
        return [
            'current_sequence' => 'integer',
        ];
    }
}
```

### 2.16 Model `User` (Otentikasi & RBAC Spatie)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'phone', 'avatar_url', 'password',
        'is_active', 'preferences', 'freelance_sales_id'
    ];

    protected $hidden = [
        'password', 'remember_token'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'preferences' => 'array',
        ];
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class, 'freelance_sales_id');
    }

    public function assignedInquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'assigned_user_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(InternalComment::class);
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'causer');
    }
}
```

---

## 3. Global Scopes: Penegakan Isolasi Multi-Tenant Agen

Untuk menjamin pemenuhan **Rule 3.9 (Isolasi Data Multi-Tenant Anti-IDOR)**, dibuat kelas scope global `App\Models\Scopes\AgentTenantScope`:

```php
namespace App\Models\Scopes;

use App\Models\FreelanceSales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AgentTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        // Staf internal (super_admin, sales_pic, finance, logistics) memiliki akses penuh tanpa pembatasan tenant
        if ($user->hasRole('super_admin') || $user->hasRole('sales_pic') || $user->hasRole('finance') || $user->hasRole('logistics')) {
            return;
        }

        // Penegakan isolasi tenant untuk mitra agen freelance
        if ($user->hasRole('freelance_sales') || $user->freelance_sales_id !== null) {
            $freelanceSalesId = $user->freelance_sales_id
                ?? FreelanceSales::where('user_id', $user->id)->value('id');

            if ($freelanceSalesId) {
                $builder->where($model->getTable() . '.freelance_sales_id', $freelanceSalesId);
            } else {
                // Pengguna ber-role agen tanpa profil valid dilarang melihat data agen lain
                $builder->whereRaw('1 = 0');
            }
        }
    }
}
```

---

## 4. Observers: Resolusi Benturan Unique Slug vs Soft Deletes

Untuk memenuhi **Rule 6.10**, dibuat `App\Observers\ProductObserver` untuk memodifikasi slug lama saat produk dihapus (soft delete), sehingga slug original dapat digunakan kembali tanpa melanggar MySQL InnoDB Unique Key Constraint:

```php
namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function deleting(Product $product): void
    {
        if (! $product->isForceDeleting()) {
            // Hindari duplikasi sufiks jika telah bertanda -deleted-
            if (! preg_match('/-deleted-\d+$/', $product->slug)) {
                $base = substr($product->slug, 0, 220);
                $product->slug = $base . '-deleted-' . time();
                $product->saveQuietly();
            }
        }
    }

    public function restoring(Product $product): void
    {
        // Bersihkan sufiks -deleted-{timestamp}
        $cleanSlug = (string) preg_replace('/-deleted-\d+$/', '', $product->slug);

        // Loop deterministik: cari kandidat slug unik tanpa risiko benturan MySQL Unique Key
        $candidate = $cleanSlug;
        $counter = 1;
        while (Product::withTrashed()->where('slug', $candidate)->where('id', '!=', $product->id)->exists()) {
            $counter++;
            $candidate = "{$cleanSlug}-{$counter}";
        }
        $product->slug = $candidate;
    }
}
```

---

## 5. Konfigurasi Relasi Polimorfik: `Relation::enforceMorphMap`

Untuk mematuhi **Rule 6.8**, seluruh relasi polimorfik wajib menggunakan alias pendek yang didaftarkan pada method `boot()` di `App\Providers\AppServiceProvider`:

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Product;
use App\Observers\ProductObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user'              => \App\Models\User::class,
            'product'           => \App\Models\Product::class,
            'inquiry'           => \App\Models\Inquiry::class,
            'quotation'         => \App\Models\Quotation::class,
            'sales_order'       => \App\Models\SalesOrder::class,
            'delivery_order'    => \App\Models\DeliveryOrder::class,
            'invoice'           => \App\Models\Invoice::class,
            'commission_payout' => \App\Models\CommissionPayout::class,
            'freelance_sales'   => \App\Models\FreelanceSales::class,
            'article'           => \App\Models\Article::class,
        ]);

        Product::observe(ProductObserver::class);
    }
}
```
