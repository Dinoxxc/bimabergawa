# Database Architecture & Core Business Logic Rules
## PT. Panca Bima Bergawa - Workspace Engineering Rules

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `AGY-PBB-RULE-DBIZ` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Autonomous Agent & Engineering Guidelines |

---

## 1. Standar Basis Data MySQL 8.0 (InnoDB Engine)

### 1.1 Konvensi Skema & Integritas Relasional
1. **Engine & Collation:** Seluruh tabel wajib menggunakan engine `InnoDB` dengan set karakter `utf8mb4` dan collation `utf8mb4_unicode_ci`.
2. **Foreign Keys:** Seluruh hubungan antar entitas wajib memiliki Foreign Key constraint eksplisit dengan aturan integritas referensial:
   - `ON DELETE RESTRICT` untuk entitas master penting (Produk, Kategori, Brand, Customer, User) guna mencegah *orphaned records*.
   - `ON DELETE CASCADE` hanya diperbolehkan untuk item turunan langsung (contoh: `quotation_items` cascade saat `quotations` dihapus draft-nya).
3. **Indexasi Kueri:** Setiap kolom yang sering difilter atau digabungkan (*JOIN*) wajib diindeks:
   - `sku` pada tabel `products` (Unique Index).
   - `status`, `created_at`, `sales_pic_id` pada tabel `inquiries` dan `quotations`.
   - `agent_id` pada tabel `inquiries` dan `commission_ledgers`.

---

## 2. Pembangkitan Nomor Urut Dokumen Bebas Race Condition

### 2.1 Implementasi `DocumentNumberService` (Atomic Lock)
Pembangkitan nomor dokumen (`INQ`, `QUO`, `SO`, `DO`, `COM`) dilarang menggunakan `Model::count() + 1` atau `Model::max('id')` karena rentan terhadap benturan konkurensi (*race condition*). Wajib menggunakan tabel `document_sequences` dengan mekanisme `SELECT ... FOR UPDATE` dan auto-retry 3x saat terjadi deadlock MySQL:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DocumentNumberService
{
    /**
     * Membangkitkan nomor urut dokumen transaksi secara atomik dan thread-safe.
     * Format: TYPE-YYYYMM-XXXX (contoh: QUO-202609-0001)
     */
    public function generateNextNumber(string $type): string
    {
        $yearMonth = date('Ym');
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return DB::transaction(function () use ($type, $yearMonth): string {
                    $sequence = DB::table('document_sequences')
                        ->where('document_type', $type)
                        ->where('year_month', $yearMonth)
                        ->lockForUpdate()
                        ->first();

                    if (! $sequence) {
                        DB::table('document_sequences')->insert([
                            'document_type' => $type,
                            'year_month' => $yearMonth,
                            'current_sequence' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $nextSeq = 1;
                    } else {
                        $nextSeq = $sequence->current_sequence + 1;
                        DB::table('document_sequences')
                            ->where('id', $sequence->id)
                            ->update([
                                'current_sequence' => $nextSeq,
                                'updated_at' => now(),
                            ]);
                    }

                    return sprintf('%s-%s-%04d', strtoupper($type), $yearMonth, $nextSeq);
                });
            } catch (Throwable $e) {
                $attempt++;
                if ($attempt >= $maxRetries) {
                    throw new RuntimeException("Gagal membangkitkan nomor dokumen [{$type}] setelah {$maxRetries} percobaan: " . $e->getMessage(), 0, $e);
                }
                usleep(50000 * $attempt); // Backoff 50ms, 100ms
            }
        }

        throw new RuntimeException("Gagal membangkitkan nomor dokumen [{$type}].");
    }
}
```

---

## 3. Logika Live Margin HPP Guard & State Machine Quotation

### 3.1 Formula Kalkulasi Margin
Kalkulasi margin kotor wajib menggunakan rumus margin penjualan:
$$\text{Margin (\%)} = \left( \frac{\text{Harga Jual Satuan} - \text{Base HPP}}{\text{Harga Jual Satuan}} \right) \times 100\%$$

### 3.2 Matriks Evaluasi & Tindakan Sistem
1. **Margin $\ge 15.00\%$ (Aman / Safe):**
   - Indikator Badge: Hijau (`#16a34a`).
   - Izin Tindakan: Sales PIC dapat langsung memfinalisasi dan menerbitkan dokumen penawaran resmi.
2. **$8.00\% \le \text{Margin} < 15.00\%$ (Peringatan / Warning):**
   - Indikator Badge: Kuning / Amber (`#d97706`).
   - Izin Tindakan: Sistem memberikan peringatan visual margin tipis namun tetap mengizinkan penerbitan dengan pencatatan log alasan diskon.
3. **Margin $< 8.00\%$ atau Harga Jual $<$ Base HPP (Kritis / Critical):**
   - Indikator Badge: Merah Menyala (`#dc2626`).
   - Izin Tindakan: **Terkunci Otomatis**. Dokumen berstatus `pending_director_approval`. Hanya peran **Super Admin (Direktur)** yang memiliki tombol otorisasi untuk menyetujui atau menolak penerbitan penawaran tersebut.

---

## 4. Snapshot Pajak PPN 11% Statis (Kepatuhan UU HPP)

Sistem wajib mengabadikan nilai PPN 11% secara statis pada saat dokumen transaksi dibuat:
```php
$vatRateApplied = 11.00; // UU Harmonisasi Peraturan Perpajakan (HPP)
$subtotal = $items->sum(fn ($item) => $item->unit_price * $item->quantity);
$vatAmount = round($subtotal * ($vatRateApplied / 100.0), 2);
$grandTotal = $subtotal + $vatAmount;

$quotation->update([
    'subtotal' => $subtotal,
    'vat_rate_applied' => $vatRateApplied,
    'vat_amount' => $vatAmount,
    'grand_total' => $grandTotal,
]);
```
*Catatan:* Dilarang melakukan *re-calculation* nilai PPN historis dari konfigurasi global saat menampilkan kembali transaksi masa lalu.

---

## 5. Pengiriman Parsial Surat Jalan (Partial Delivery Order Fulfillment)

### 5.1 Validasi Kuantitas & Pelacakan `remaining_qty`
Pada pemenuhan Sales Order (SO) yang melibatkan pengiriman bertahap (parsial):
1. Setiap item pada Sales Order memiliki kolom `ordered_qty`, `fulfilled_qty`, dan `remaining_qty`.
2. Saat Logistik membuat Surat Jalan (DO), sistem memvalidasi bahwa `delivery_qty <= remaining_qty`.
3. Jika kuantitas melebihi sisa pesanan, sistem menolak transaksi dengan pesan kesalahan `OverDeliveryQuantityException`.
4. Setelah DO terbit:
   - `fulfilled_qty += delivery_qty`
   - `remaining_qty = ordered_qty - fulfilled_qty`
5. Jika seluruh item memiliki `remaining_qty === 0`, status Sales Order otomatis beralih menjadi `completed`. Jika masih ada sisa, status menjadi `partially_fulfilled`.

---

## 6. Buku Besar Komisi Agen Freelance (Agent Commission Ledger)

### 6.1 State Machine Alur Komisi
1. **Pencatatan Akrual (`earned`):** Dibuat otomatis saat Sales Order yang berasal dari inkuiri mitra agen telah diverifikasi lunas (*Invoice Paid*) oleh bagian Finance.
   $$\text{Nilai Komisi} = \text{Subtotal SO} \times \left( \frac{\text{Komisi Agen (\%)}}{100} \right)$$
2. **Pengajuan Penarikan (`requested`):** Mitra agen mengajukan pencairan dana komisi melalui portal mandiri (minimal pencairan Rp 500.000).
3. **Verifikasi Finance (`approved`):** Finance memeriksa kesesuaian nomor rekening bank agen.
4. **Pencairan Sukses (`paid`):** Finance mengunggah bukti transfer perbankan, saldo komisi terpotong, dan sistem mengirimkan notifikasi email ke agen.
