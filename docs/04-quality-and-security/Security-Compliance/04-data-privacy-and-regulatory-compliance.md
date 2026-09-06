# 04. Kepatuhan Privasi Data PDP, Regulasi Pajak, & Jejak Audit
## Security Policy & Regulatory Compliance Suite (Security-Compliance)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SEC-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Compliance Officer | Draf Awal | Kerangka dasar kepatuhan privasi data pelanggan |
| `1.1.0` | 03 Sep 2026 | Legal & Regulatory Analyst | Ditinjau | Penyelarasan pasal UU PDP No. 27/2022 & retensi pajak UU KUP |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Spesifikasi teknis enkripsi field database & Spatie ActivityLog |
| `1.3.0` | 05 Sep 2026 | Principal Security Architect | Disetujui | Pemisahan berkas mandiri kepatuhan privasi data & zero emoji |

---

## 1. Kepatuhan Undang-Undang Pelindungan Data Pribadi (UU PDP No. 27/2022)

PT. Panca Bima Bergawa bertindak sebagai **Pengendali Data Pribadi** (*Data Controller*) untuk data klien B2B dan Mitra Agen Freelance yang terdaftar pada sistem:

### 1.1 Klasifikasi Data Pribadi yang Dikelola

| Kategori Data | Contoh Elemen Data | Tingkat Sensitivitas | Kebijakan Enkripsi & Proteksi |
| :--- | :--- | :---: | :--- |
| **Data Prospek / Klien** | Nama PIC, Nomor WhatsApp, Email Kantor, Jabatan | Standar | Disimpan terproteksi RBAC, akses dibatasi pada PIC Sales & Admin |
| **Data Identitas Agen** | NIK (KTP), Foto KTP, NPWP | Tinggi | Enkripsi pada level database (*AES-256-CBC*) & berkas privat |
| **Data Finansial Agen** | Nomor Rekening Bank, Nama Pemilik Rekening | Tinggi | Enkripsi field Eloquent `$casts = ['account_number' => 'encrypted']` |
| **Data Transaksi B2B** | Nilai Purchase Order, Diskon, Margin Komisi | Rahasia Bisnis | Isolasi RBAC ketat, dilarang diakses oleh peran non-otorisasi |

### 1.2 Implementasi Enkripsi Field Sensitif (Model Casts)

Pada level Eloquent ORM, data identitas dan perbankan agen freelance dienkripsi secara transparan menggunakan kunci `APP_KEY` Laravel:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AgentProfile extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'nik_ktp',
        'npwp_number',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'ktp_file_path',
        'commission_rate_percent',
    ];

    /**
     * Enkripsi otomatis saat penyimpanan ke database (At-Rest Encryption)
     */
    protected $casts = [
        'nik_ktp' => 'encrypted',
        'npwp_number' => 'encrypted',
        'bank_account_number' => 'encrypted',
        'commission_rate_percent' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['bank_name', 'bank_account_holder', 'commission_rate_percent'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Data profil agen telah di-{$eventName}");
    }
}
```

---

## 2. Kepatuhan Regulasi Perpajakan & Retensi Data Keuangan

Sesuai dengan **Pasal 28 Undang-Undang Ketentuan Umum dan Tata Cara Perpajakan (UU KUP)** dan **UU Harmonisasi Peraturan Perpajakan (UU HPP)**:

1. **Snapshot Tarif Pajak Statis:** Setiap transaksi penawaran resmi (*Quotation*) dan faktur (*Invoice*) wajib mencatat persentase PPN yang berlaku saat transaksi diterbitkan (`vat_rate_applied`). Hal ini menjamin angka transaksi historis tidak berubah ketika tarif PPN nasional disesuaikan di masa mendatang.
2. **Kekekalan Data Finansial (Immutability):** Faktur komersial yang telah berstatus `paid` atau `issued` dikunci dari proses penyuntingan dan penghapusan langsung. Koreksi hanya dapat dilakukan melalui penerbitan *Credit Note* atau faktur pembatalan formal.
3. **Periode Retensi Data 10 Tahun:** Seluruh rekaman inkuiri resmi, PO, faktur, dan bukti transfer komisi dipertahankan di database selama minimal 10 (sepuluh) tahun fiskal sebelum diarsipkan ke penyimpanan *cold storage* terpisah.

---

## 3. Jejak Audit & Forensik Operasional (Spatie ActivityLog)

Seluruh tindakan mutasi data kritis dicatat secara otomatis ke dalam tabel `activity_log` untuk kebutuhan audit kepatuhan internal dan pengawasan oleh Direksi:

### 3.1 Parameter Kejadian yang Wajib Dicatat

1. **Perubahan Status Komersial:** Perubahan status inkuiri dari `lead` ke `quoted`, `approved`, atau `rejected`.
2. **Kalkulasi & Pembayaran Komisi:** Penerbitan dan pencairan komisi mitra agen freelance oleh Departemen Keuangan.
3. **Mutasi Data Master Katalog:** Perubahan harga dasar, harga jual rekomendasi, dan status publikasi produk.
4. **Perubahan Hak Akses / Role:** Pemberian peran, pencabutan hak akses, dan pergantian email pengguna internal.

### 3.2 Konfigurasi Pembersihan Log (Log Retention Schedule)

Tabel `activity_log` dibersihkan secara terjadwal untuk menjaga efisiensi penyimpanan database MySQL pada hosting cPanel:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Bersihkan catatan log yang berusia lebih dari 90 hari setiap hari Minggu malam
    $schedule->command('activitylog:clean --days=90')->weeklyOn(0, '02:00');
}
```

---

## 4. Rencana Tanggap Insiden Keamanan (Incident Response Plan)

Jika terjadi anomali akses atau dugaan kebocoran data (*Data Breach*), tim teknis menerapkan protokol penanganan darurat 4 tahap:

```
[TAHAP 1: DETEKSI & IDENTIFIKASI]
Identifikasi vektor ancaman melalui inspeksi activity_log dan log server LiteSpeed.
                          │
                          ▼
[TAHAP 2: ISOLASI & PENGENDALIAN]
1. Aktifkan Maintenance Mode via Artisan (php artisan down --secret="pbb-emergency").
2. Putuskan seluruh sesi aktif dengan mengosongkan tabel sessions.
3. Rotasi kredensial kunci aplikasi (APP_KEY), kata sandi database, dan API key Mailer.
                          │
                          ▼
[TAHAP 3: PEMULIHAN & REMEDIASI]
1. Patching celah keamanan kode atau konfigurasi web server.
2. Restore database dari snapshot backup terverifikasi jika terjadi manipulasi data.
3. Uji penetrasi lokal sebelum membuka kembali akses publik.
                          │
                          ▼
[TAHAP 4: NOTIFIKASI & PELAPORAN]
Sesuai Pasal 46 UU PDP No. 27/2022, pemberitahuan tertulis disampaikan kepada subjek data
dan lembaga pengawas data pribadi dalam waktu maksimal 3 x 24 jam.
```
