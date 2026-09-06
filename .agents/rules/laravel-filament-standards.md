# Laravel 11 & Filament 3 Architectural Standards
## PT. Panca Bima Bergawa - Workspace Engineering Rules

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `AGY-PBB-RULE-LARA` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Autonomous Agent & Engineering Guidelines |

---

## 1. Prinsip Penulisan Kode PHP 8.3 & Laravel 11

### 1.1 Strict Types & Explicit Type Hinting
Setiap berkas PHP wajib diawali dengan deklarasi strict types:
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quotation;
use App\Models\QuotationItem;

class QuotationCalculationService
{
    /**
     * Menghitung margin kotor persentase berdasarkan harga jual dan HPP dasar.
     */
    public function calculateHppMargin(float $unitPrice, float $baseHpp): float
    {
        if ($unitPrice <= 0.0) {
            return 0.0;
        }

        return round((($unitPrice - $baseHpp) / $unitPrice) * 100.0, 2);
    }

    /**
     * Mengevaluasi status guard margin untuk menentukan kebutuhan approval direktur.
     * Return: 'safe' (>=15%), 'warning' (8-14.99%), 'critical' (<8% / di bawah HPP)
     */
    public function evaluateMarginStatus(float $marginPercent): string
    {
        return match (true) {
            $marginPercent >= 15.00 => 'safe',
            $marginPercent >= 8.00 => 'warning',
            default => 'critical',
        };
    }
}
```

### 1.2 Service Layer Pattern (Larangan Fat Controller & Fat Model)
Controller dan Filament Page dilarang memuat kalkulasi bisnis langsung. Seluruh logika transaksi wajib didelegasikan ke kelas Service pada `app/Services/`:

| Nama Kelas Service | File Path | Tugas Utama & Tanggung Jawab Teknis |
| :--- | :--- | :--- |
| `DocumentNumberService` | `app/Services/DocumentNumberService.php` | Pembangkitan nomor urut transaksi (`INQ`, `QUO`, `SO`, `DO`, `COM`) via Atomic Lock `SELECT ... FOR UPDATE` dengan 3x auto-retry saat deadlock. |
| `QuotationCalculationService` | `app/Services/QuotationCalculationService.php` | Perhitungan live margin HPP, evaluasi guard margin (<15% warning, <8% approval lock), subtotal, PPN 11% snapshot, dan grand total. |
| `ProductImageProcessingService` | `app/Services/ProductImageProcessingService.php` | Rasterisasi gambar via Intervention Image 3 (GD Driver), konversi WebP 82%, penskalaan max width/height 1600px, dan pembersihan EXIF metadata. |
| `SkuParserService` | `app/Services/SkuParserService.php` | Regex parser teks massal textarea pada drawer RFQ, memisahkan SKU string, delimiter, dan kuantitas numerik (default 1 jika omitted). |
| `CommissionPayoutService` | `app/Services/CommissionPayoutService.php` | Manajemen ledger komisi agen freelance, kalkulasi saldo tersedia, verifikasi rekening, dan pencatatan riwayat penarikan dana. |
| `BulkSkuNormalizationService` | `app/Services/BulkSkuNormalizationService.php` | Normalisasi string SKU (uppercase, trim whitespace, standardisasi pemisah dash, pembersihan karakter non-alphanumeric selain strip). |
| `DeliveryOrderFulfillmentService` | `app/Services/DeliveryOrderFulfillmentService.php` | Validasi kuantitas pengiriman parsial, pengecekan over-delivery, dan pembaruan field `remaining_qty` serta status Sales Order. |
| `SecureFileUploadService` | `app/Services/SecureFileUploadService.php` | Validasi biner Magic Bytes kernel (`finfo`), penamaan acak UUID v4, isolasi storage privat (non-public), dan serving via authenticated stream. |

---

## 2. Standar Form Request & Validasi Input

Dilarang melakukan validasi langsung di dalam Controller (`$request->validate()`). Selalu gunakan kelas `FormRequest` mandiri pada `app/Http/Requests/`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePublicInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'contact_person' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc,dns', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^([0-9\s\-\+\(\)]*)$/', 'min:9', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['required', 'string', 'max:50'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99999'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'boq_attachment' => [
                'nullable',
                File::types(['pdf', 'xlsx', 'xls', 'docx', 'jpg', 'jpeg', 'png'])
                    ->max(5 * 1024), // Maksimal 5 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'contact_person.required' => 'Nama kontak penanggung jawab wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'phone.required' => 'Nomor telepon/WhatsApp wajib diisi.',
            'items.required' => 'Daftar item permintaan penawaran (RFQ) minimal harus memuat 1 produk.',
            'boq_attachment.max' => 'Ukuran berkas lampiran BOQ tidak boleh melebihi 5 MB.',
        ];
    }
}
```

---

## 3. Standar Eloquent Model, Enkripsi, & Multi-Tenant Scopes

### 3.1 Enkripsi At-Rest Data Pribadi Sensitif (Kepatuhan UU PDP No. 27/2022)
Kolom identitas NIK KTP, NPWP, dan nomor rekening perbankan pada model `AgentProfile` wajib dienkripsi otomatis:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentProfile extends Model
{
    use HasFactory;

    protected $table = 'agent_profiles';

    protected $fillable = [
        'user_id',
        'nik_ktp',
        'npwp_number',
        'bank_name',
        'bank_branch',
        'bank_account_number',
        'bank_account_name',
        'commission_rate_percent',
        'status',
    ];

    protected $casts = [
        'nik_ktp' => 'encrypted',
        'npwp_number' => 'encrypted',
        'bank_account_number' => 'encrypted',
        'commission_rate_percent' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commissionLedgers(): HasMany
    {
        return $this->hasMany(CommissionLedger::class, 'agent_id', 'user_id');
    }
}
```

### 3.2 Global Tenant Scope untuk Agen Freelance (Anti-IDOR)
Model `Inquiry` wajib menyematkan Global Scope `AgentTenantScope` untuk membatasi akses kueri secara transparan hanya pada record yang dibuat oleh mitra agen yang bersangkutan:
```php
<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AgentTenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check() && auth()->user()->hasRole('mitra-agen')) {
            $builder->where('agent_id', auth()->id());
        }
    }
}
```

Penyematan Global Scope pada Model `Inquiry`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Scopes\AgentTenantScope;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new AgentTenantScope());
    }

    // ... relasi dan properti lainnya
}
```

---

## 4. Standar Filament Admin 3.x & Spatie RBAC

### 4.1 Pemisahan Panel (Admin Panel vs Agent Portal)
Sistem memisahkan dua panel Filament secara independen:
1. **Panel Admin Internal (`/` pada `app.bimabergawa.com`):** Dikelola oleh Super Admin, Sales PIC, Finance, Logistik.
2. **Panel Portal Agen (`/agent` pada `app.bimabergawa.com`):** Dikelola khusus untuk mitra agen freelance dengan navigasi terbatas pada inkuiri mandiri dan ledger komisi.

### 4.2 Visibilitas Kolom Sensitif Berdasarkan Otorisasi Spatie
Kolom Base HPP dan Margin Laba pada tabel penawaran wajib disembunyikan dari staf non-otorisasi (Logistik dan Mitra Agen):
```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('base_hpp')
    ->label('Modal Dasar (HPP)')
    ->money('IDR')
    ->visible(fn (): bool => auth()->user()->can('view_hpp_margin')),

TextColumn::make('margin_percent')
    ->label('Margin Laba')
    ->badge()
    ->color(fn (float $state): string => match (true) {
        $state >= 15.0 => 'success',
        $state >= 8.0 => 'warning',
        default => 'danger',
    })
    ->visible(fn (): bool => auth()->user()->can('view_hpp_margin'));
```

### 4.3 Action Modal Approval Direktur untuk Margin Kritis
Pada Quotation Resource, sediakan aksi persetujuan khusus yang hanya dapat dieksekusi oleh Super Admin / Direktur:
```php
use Filament\Tables\Actions\Action;

Action::make('approve_critical_margin')
    ->label('Setujui Margin Khusus')
    ->icon('heroicon-m-check-badge')
    ->color('warning')
    ->requiresConfirmation()
    ->modalHeading('Konfirmasi Persetujuan Margin di Bawah Standar (<15%)')
    ->modalDescription('Apakah Anda yakin ingin menyetujui penawaran ini dengan margin di bawah standar perusahaan?')
    ->visible(fn (Quotation $record): bool => 
        $record->status === 'pending_director_approval' && 
        auth()->user()->hasRole('super-admin')
    )
    ->action(function (Quotation $record): void {
        $record->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    });
```

---

## 5. Standar Pengujian Otomatis (Pest Testing Architecture)

Setiap fitur wajib dilengkapi dengan test suite Pest yang mencakup:
1. **Unit Test Service:** Memverifikasi logika kalkulasi margin, normalisasi SKU, dan parser teks.
2. **Feature Test Controller & Filament:** Memverifikasi otentikasi Passwordless OTP, validasi form, dan middleware subdomain.
3. **Architecture Test:** Menegakkan kebersihan arsitektur kode:

```php
<?php

declare(strict_types=1);

test('services are strictly isolated and strongly typed', function () {
    expect('App\Services')
        ->toHaveMethodsWithReturnType()
        ->toOnlyBeUsedIn([
            'App\Http\Controllers',
            'App\Filament',
            'App\Console\Commands',
            'App\Jobs',
            'App\Services',
        ]);
});

test('models do not execute raw http requests', function () {
    expect('App\Models')
        ->toOnlyUse([
            'Illuminate\Database',
            'App\Models',
            'App\Enums',
        ]);
});

test('controllers always return response or view', function () {
    expect('App\Http\Controllers')
        ->toHaveMethodsWithReturnType();
});
```
