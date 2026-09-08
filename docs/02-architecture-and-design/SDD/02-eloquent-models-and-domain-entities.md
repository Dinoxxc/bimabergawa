# 02. Model Eloquent & Entitas Domain
## System Technical Design Document (SDD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SDD-02` |
| **Versi Dokumen** | 1.5.1 |
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

---

## 1. Konvensi & Struktur Model Eloquent

Seluruh model domain ditempatkan di direktori `app/Models/`, mewarisi `Illuminate\Database\Eloquent\Model`, serta memanfaatkan fitur modern Laravel 11:

- **Mass Assignment:** Menggunakan array `$fillable` eksplisit untuk seluruh kolom input.
- **Tipe Data Casts:** Menggunakan method `casts()` modern untuk casting desimal, boolean, datetime, dan enkripsi data sensitif (`'encrypted'`).
- **Integritas Relasi:** Menggunakan type-hinting resmi (`BelongsTo`, `HasMany`, `HasOne`).

---

## 2. Definisi Model Utama & Relasi Bisnis

### 2.1 Model `Product`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    protected $fillable = [
        'brand_id', 'category_id', 'successor_product_id', 'name', 'slug',
        'model_number', 'cost_price', 'description', 'datasheet_path',
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
        return $this->belongsTo(Product::class, 'successor_product_id');
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    // Scopes
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

---

### 2.2 Model `Inquiry` & `InquiryTelemetry`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Inquiry extends Model
{
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
        // Global Scope Tenant Isolation untuk Freelance Sales
        static::addGlobalScope('freelance_agent_scope', function (Builder $builder) {
            if (auth()->check() && auth()->user()->hasRole('freelance_sales')) {
                $builder->where('freelance_sales_id', auth()->user()->freelance_sales_id);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class);
    }

    public function telemetry(): HasOne
    {
        return $this->hasOne(InquiryTelemetry::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class);
    }

    public function quotation(): HasOne
    {
        return $this->hasOne(Quotation::class);
    }

    // Scope untuk Peringatan Stale Leads (> 2 jam kerja belum dihubungi)
    public function scopeStale(Builder $query): Builder
    {
        return $query->where('status', 'new')
            ->where('created_at', '<=', now()->subHours(2));
    }
}
```

---

### 2.3 Model `Quotation` & `QuotationPaymentTerm`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_number', 'revision_number', 'inquiry_id', 'user_id',
        'freelance_sales_id', 'customer_name', 'company_name',
        'delivery_terms', 'validity_days', 'expires_at', 'subtotal',
        'discount_amount', 'dpp', 'tax_amount', 'grand_total',
        'total_cost', 'gross_profit_margin', 'is_approved_by_director',
        'status', 'pdf_path'
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'dpp' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'gross_profit_margin' => 'decimal:2',
            'is_approved_by_director' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('freelance_agent_scope', function (Builder $builder) {
            if (auth()->check() && auth()->user()->hasRole('freelance_sales')) {
                $builder->where('freelance_sales_id', auth()->user()->freelance_sales_id);
            }
        });
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function freelanceSales(): BelongsTo
    {
        return $this->belongsTo(FreelanceSales::class);
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

    // Scope untuk Penawaran Mendekati Jatuh Tempo (H-3)
    public function scopeExpiringInDays(Builder $query, int $days = 3): Builder
    {
        return $query->whereIn('status', ['sent', 'approved'])
            ->whereDate('expires_at', '=', now()->addDays($days)->toDateString());
    }
}
```

---

### 2.4 Model `SalesOrder` & `DeliveryOrder`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model
{
    protected $fillable = [
        'order_number', 'quotation_id', 'customer_po_number',
        'customer_po_date', 'status', 'total_amount'
    ];

    protected function casts(): array
    {
        return [
            'customer_po_date' => 'date',
            'total_amount' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function commissionPayout(): HasOne
    {
        return $this->hasOne(CommissionPayout::class);
    }
}
```

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrder extends Model
{
    protected $fillable = [
        'delivery_number', 'sales_order_id', 'courier_name',
        'tracking_number', 'tracking_url', 'shipping_address',
        'recipient_pic_name', 'recipient_phone', 'total_packages',
        'total_weight_kg', 'status', 'proof_of_delivery_path', 'shipped_at'
    ];

    protected function casts(): array
    {
        return [
            'total_weight_kg' => 'decimal:2',
            'shipped_at' => 'datetime',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }
}
```

---

### 2.5 Model `FreelanceSales`, `CommissionTier` & `CommissionPayout`
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FreelanceSales extends Model
{
    protected $fillable = [
        'commission_tier_id', 'name', 'referral_code', 'nik',
        'ktp_file_path', 'bank_name', 'bank_account_number',
        'bank_account_name', 'industry_focus', 'status',
        'rejection_reason', 'approved_at'
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'approved_at' => 'datetime',
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(CommissionTier::class, 'commission_tier_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(CommissionPayout::class);
    }
}
```
---

### 2.15 Model `Invoice` & `InvoiceItem` (Modul Penagihan Komersial B2B)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'sales_order_id',
        'quotation_id',
        'payment_term_id',
        'customer_name',
        'company_name',
        'invoice_date',
        'due_date',
        'subtotal',
        'vat_rate_applied',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'currency',
        'status',
        'payment_method',
        'payment_proof_path',
        'paid_at',
        'notes',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'vat_rate_applied' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function getRemainingBalanceAttribute(): float
    {
        return (float) max(0, $this->grand_total - $this->paid_amount);
    }
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(QuotationPaymentTerm::class, 'payment_term_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
```

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'item_name',
        'model_number',
        'quantity',
        'unit_price',
        'total_price',
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
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

---

### 2.16 Model `ActivityLog` (Immutable System Audit Trail)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
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
}
```
