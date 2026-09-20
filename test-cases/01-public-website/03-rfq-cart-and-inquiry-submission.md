# Test Cases: Keranjang RFQ, Bulk SKU Parser, & Form Inkuiri Publik
## Domain 01: Public Website (bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-PUB-03` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | QA Engineer | Draf Awal | Pengujian keranjang belanja RFQ dan form inkuiri |
| `1.1.0` | 03 Sep 2026 | Test Automation Engineer | Ditinjau | Penambahan uji Bulk SKU Parser regex & validasi lampiran BOQ |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan rate limiting anti-spam & inspeksi magic byte |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |
| `1.4.0` | 20 Sep 2026 | Security & QA Architect | Disetujui | Penambahan 6 skenario uji keamanan 4 lapis pelacak pengadaan publik (Two-Key Verification, Signed URL, anti-IDOR, rate limit, & PDF guard) (TC-PUB-RFQ-017 s.d. 022) |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-02` (Spesifikasi RFQ Cart & Bulk SKU Parser)
  - `DOC-PBB-SRS-03` (Kebutuhan Fungsional FR-RFQ-01 s.d. FR-RFQ-06)
  - `DOC-PBB-SDD-05` (Alpine.js RFQ Store & LocalStorage Persistence)
  - `DOC-PBB-UIUX-03` (Wireframe RFQ Drawer, Quick Paste, & Form Inkuiri)
- **Modul Sistem:** Modul Permintaan Penawaran Harga (Request for Quotation / RFQ)
- **Target Kelas / Controller / Service:**
  - `App\Http\Controllers\PublicInquiryController`
  - `App\Services\SkuParserService`
  - `App\Http\Requests\StorePublicInquiryRequest`
  - `App\Jobs\SendInquiryConfirmationMail`
- **Target Method:** `store()`, `parseRawText()`, `validateAttachment()`, `handle()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-PUB-RFQ-001`** | Positif | Penambahan Produk ke Keranjang RFQ via Tombol Katalog | Pengguna berada di katalog/detail produk | Klik tombol "+ Tambah ke RFQ", atur Qty = 5 | Badge counter RFQ di header bertambah (+1 SKU, total 5 pcs), drawer terbuka menampilkan rincian item, data tersimpan di LocalStorage browser. | Not Executed |
| **`TC-PUB-RFQ-002`** | Positif | Pembaruan Kuantitas (*Update Quantity*) di Drawer RFQ | Drawer RFQ memuat produk | Ubah angka kuantitas dari 5 menjadi 25 | Subtotal kuantitas diperbarui seketika (*reactive Alpine.js*), LocalStorage langsung tersinkronisasi tanpa jeda / refresh. | Not Executed |
| **`TC-PUB-RFQ-003`** | Positif | Penghapusan Item Individual dari Keranjang RFQ | Drawer RFQ memuat 3 jenis produk | Klik tombol hapus (*trash icon*) pada item ke-2 | Item ke-2 terhapus dari daftar keranjang, counter header berkurang, 2 item lainnya tetap utuh tersimpan di LocalStorage. | Not Executed |
| **`TC-PUB-RFQ-004`** | Positif | Pengosongan Seluruh Keranjang RFQ (*Clear All*) | Keranjang RFQ memuat beberapa item | Klik tombol "Kosongkan Keranjang" & konfirmasi | Seluruh item terhapus, drawer menampilkan antarmuka keranjang kosong dengan tombol "Jelajahi Katalog", LocalStorage dibersihkan. | Not Executed |
| **`TC-PUB-RFQ-005`** | Positif | Persistensi Keranjang RFQ Lintas Tab & Saat Page Refresh | Keranjang RFQ terisi 3 item | Refresh halaman atau buka tab baru browser | Seluruh 3 item produk dan kuantitasnya tetap tersimpan utuh dan langsung termuat saat halaman baru dibuka. | Not Executed |
| **`TC-PUB-RFQ-006`** | Positif | Penguraian Teks Massal (*Bulk SKU Parser*) Format Standar | Drawer RFQ terbuka pada tab "Quick Paste" | Input teks: `SCH-MCB-16A 100\nPHI-LED-12W 50` | `SkuParserService` mengurai teks menjadi 2 item valid beserta kuantitas masing-masing (100 dan 50) dan memasukkannya ke keranjang RFQ. | Not Executed |
| **`TC-PUB-RFQ-007`** | Positif | Penguraian Teks Massal Tanpa Kuantitas (Default Qty = 1) | Tab Quick Paste aktif | Input teks: `ABB-ISOL-32A` (tanpa angka jumlah) | Parser mendeteksi SKU dan secara otomatis memberikan nilai kuantitas default = 1 pcs. | Not Executed |
| **`TC-PUB-RFQ-008`** | Positif | Pengiriman Form Inkuiri Lengkap dengan Lampiran BOQ | Keranjang RFQ terisi minimal 1 item | Form terisi valid + Berkas `boq-tender.pdf` (1.2 MB) | HTTP 200/302. Data tersimpan di database (`inquiries` & `inquiry_items`), berkas tersimpan di private storage, pengguna dialihkan ke halaman sukses dengan nomor registrasi unik. | Not Executed |
| **`TC-PUB-RFQ-009`** | Positif | Dispatch Job Pengiriman Email Notifikasi | Inkuiri berhasil terkirim | Email valid: `purchasing@kontraktor.co.id` | Job `SendInquiryConfirmationMail` dimasukkan ke antrean (*queue*) dan mengirimkan rincian inkuiri ke email klien dan notifikasi ke tim sales internal. | Not Executed |
| **`TC-PUB-RFQ-010`** | Negatif | Validasi Field Wajib Kosong (*Required Fields*) | Form inkuiri dibuka | Payload dengan field `company_name`, `contact_person`, `email` kosong | HTTP 422 Unprocessable Entity. Muncul pesan error validasi di bawah setiap input: "Nama Perusahaan wajib diisi", "Email wajib diisi". | Not Executed |
| **`TC-PUB-RFQ-011`** | Negatif | Validasi Format Email Tidak Standar / Domain Tidak Valid | Form inkuiri terisi | Input email: `bukan-email-valid` atau `test@domain-palsu-tidak-ada.xyz` | HTTP 422 Unprocessable Entity. Validasi `email:rfc,dns` menolak input dengan pesan: "Format alamat email tidak valid". | Not Executed |
| **`TC-PUB-RFQ-012`** | Negatif | Pengunggahan Lampiran Berkas Melebihi Batas Ukuran (> 5 MB) | Form inkuiri terisi | Unggah berkas `lampiran-boq.pdf` ukuran 6.2 MB | Sistem menolak pengiriman form dan memunculkan notifikasi error: "Ukuran berkas lampiran maksimal adalah 5 MB". | Not Executed |
| **`TC-PUB-RFQ-013`** | Keamanan | Penolakan Eksekusi Berkas Berbahaya (*MIME Magic Byte Inspection*) | Form inkuiri terisi | Unggah berkas `exploit.php.pdf` (berisi skrip PHP) | Validasi Magic Byte menolak berkas dengan pesan: "Format berkas tidak diizinkan". Berkas sama sekali tidak tersimpan di disk server. | Not Executed |
| **`TC-PUB-RFQ-014`** | Keamanan | Perlindungan Anti-Spam Honeypot | Bot otomatis mengisi form | Field tersembunyi `website_url` (honeypot) terisi nilai | Server menolak permintaan secara senyap (*silent drop*) tanpa menyimpan data ke tabel `inquiries` dan mengembalikan respons sukses palsu. | Not Executed |
| **`TC-PUB-RFQ-015`** | Keamanan | Penegakan Batas Permintaan (*Rate Limiting Form Submission*) | Pengguna/bot mengirim submit form berulang | Pengiriman > 3 kali dalam 10 menit dari IP sama | HTTP 429 Too Many Requests. Sistem mengunci form sementara dan menampilkan sisa waktu tunggu cooldown. | Not Executed |
| **`TC-PUB-RFQ-016`** | Konkurensi | Pencegahan Double-Submit pada Tombol Kirim (*Idempotency*) | Pengguna mengklik tombol "Kirim Inkuiri" 2x cepat | Double click cepat pada tombol submit dalam jeda 100ms | Tombol langsung berstatus `disabled` dengan indikator loading saat klik pertama, hanya tepat 1 inkuiri yang tercatat di database (*no duplicate records*). | Not Executed |
| **`TC-PUB-RFQ-017`** | Positif | Pelacakan Mandiri via Two-Key Verification Valid (`POST /lacak-rfq/search`) | Inkuiri `PBB/INQ/2026/09/0021` terdaftar dengan no WA `081234567890` | Input No Dokumen & No WA yang cocok | HTTP 200 OK. Menampilkan linimasa progres pengadaan, status transaksi, resi kurir, dan data PIC ter-masking (Ahm** Fa***) tanpa mengekspos harga nominal. | Not Executed |
| **`TC-PUB-RFQ-018`** | Keamanan | Penolakan Pelacakan dengan Kontak Tidak Cocok (Anti-IDOR / Anti-Enumeration) | Penyerang menebak nomor dokumen valid `PBB/INQ/2026/09/0021` | Input No Dokumen benar tetapi No WA/Email acak | HTTP 404 / 422 Unprocessable Entity. Menolak akses dengan pesan seragam "Kombinasi nomor dokumen dan kontak verifikasi tidak cocok". Menutup 100% celah enumerasi data pihak lain. | Not Executed |
| **`TC-PUB-RFQ-019`** | Positif | Akses Instan Pelacakan via Cryptographic Temporary Signed URL Email | Klien menerima email notifikasi resmi RFQ | Klik tautan ber-signature HMAC-SHA256 dari inbox email | HTTP 200 OK. Membuka detail tahapan pengadaan secara langsung tanpa perlu menginput kontak verifikasi manual di form. | Not Executed |
| **`TC-PUB-RFQ-020`** | Keamanan | Penolakan Signed URL yang Dirusak (*Tampered*) atau Kedaluwarsa | Klien membuka link email yang diubah parameternya atau > 30 hari | Akses URL dengan parameter `signature` tidak valid | HTTP 403 Forbidden. Sistem menolak akses instan dan mengarahkan pengguna ke form verifikasi dua kunci manual. | Not Executed |
| **`TC-PUB-RFQ-021`** | Keamanan | Pengunduhan Berkas PDF Penawaran via Signed Token Privat | Klien terverifikasi di portal pelacakan | Klik "Unduh PDF Penawaran Resmi" | HTTP 200 Stream PDF dari `storage/app/private/documents/` menggunakan one-time token (15 menit). Akses direct file via `public/storage/` terblokir 403. | Not Executed |
| **`TC-PUB-RFQ-022`** | Keamanan | Penegakan Rate Limiting & Anti-Brute Force pada Pencarian Pelacakan | Bot melakukan looping nomor dokumen | Request > 5 kali per menit dari IP sama pada `/lacak-rfq/search` | HTTP 429 Too Many Requests. Sistem mengunci endpoint pencarian dan memicu Cloudflare Turnstile CAPTCHA. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-PUB-RFQ-006 (Eksekusi Bulk SKU Parser)

1. **Langkah 1:** Buka `https://bimabergawa.com/katalog`.
2. **Langkah 2:** Buka Drawer RFQ -> Pilih tab **Quick Paste / Bulk SKU**.
3. **Langkah 3:** Masukkan teks berikut pada textarea:
   ```
   SCH-MCB-16A    100
   PHI-LED-12W    50
   ABB-ISOL-32A   20
   ```
4. **Langkah 4:** Klik tombol **"Proses & Masukkan ke RFQ"**.
5. **Verifikasi Teknis:**
   - Permintaan diproses oleh fungsi `SkuParserService::parseRawText()`.
   - Ketiga SKU dicocokkan dengan database master produk.
   - Tabel keranjang RFQ langsung terisi 3 baris item dengan kuantitas yang sesuai (100, 50, dan 20).

### 3.2 Prosedur Uji TC-PUB-RFQ-016 (Uji Pencegahan Double-Submit)

1. **Langkah 1:** Buka form RFQ dengan 1 item terisi.
2. **Langkah 2:** Isi field identitas perusahaan secara lengkap.
3. **Langkah 3:** Buka console developer tools, jalankan script untuk men-trigger event submit ganda serentak:
   ```javascript
   const form = document.querySelector('form#inquiry-form');
   form.requestSubmit();
   form.requestSubmit();
   ```
4. **Verifikasi Teknis:**
   - Client-side state `isSubmitting = true` mencegah pengiriman kedua.
   - Hanya 1 baris inkuiri baru yang tercatat pada database MySQL `inquiries`.

### 3.3 Prosedur Uji TC-PUB-RFQ-018 (Uji Penolakan Akses Tracking Tanpa Kontak Cocok / Anti-IDOR)

1. **Langkah 1:** Buka `https://bimabergawa.com/lacak-rfq`.
2. **Langkah 2:** Masukkan Nomor Dokumen valid: `PBB/INQ/2026/09/0021`.
3. **Langkah 3:** Masukkan Kontak Verifikasi salah: `089999999999` (bukan nomor kontak pemohon asli).
4. **Langkah 4:** Klik tombol **"Verifikasi & Lacak Status"**.
5. **Verifikasi Teknis:**
   - Server merespons dengan HTTP 422 / 404 pesan seragam: "Kombinasi nomor dokumen dan kontak verifikasi tidak cocok".
   - Tidak ada rincian data klien, status PO, nama proyek, atau berkas penawaran yang dibocorkan ke penyerang.
   - Rate limiter mencatat 1 kali percobaan gagal dari alamat IP tersebut.
