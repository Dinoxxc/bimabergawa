# Test Cases: WhatsApp Direct Inquiry, Generator Pesan, & Telemetri
## Domain 01: Public Website (bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-PUB-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | QA Engineer | Draf Awal | Pengujian tautan tombol WhatsApp statis |
| `1.1.0` | 03 Sep 2026 | Integration Specialist | Ditinjau | Penambahan generator pesan dinamis produk & pencatatan telemetri |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan mitigasi panjang URL WhatsApp multi-item |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 15 skenario uji mendalam & standardisasi status Not Executed |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-02` (Spesifikasi Jalur Komunikasi Cepat WhatsApp B2B)
  - `DOC-PBB-SRS-02` (Kebutuhan Fungsional FR-TEL-01 & FR-RFQ-05)
  - `DOC-PBB-API-01` (Rute Publik & Endpoint Telemetri Redirection)
  - `DOC-PBB-UIUX-02` (Floating Action Button & Wireframe Kontak)
- **Modul Sistem:** Modul Saluran Inkuiri Cepat WhatsApp & Telemetri
- **Target Kelas / Controller / Service:**
  - `App\Http\Controllers\WhatsAppRedirectController`
  - `App\Services\WhatsAppMessageGeneratorService`
  - `App\Models\TelemetryLog`
- **Target Method:** `redirect()`, `generateCatalogInquiryText()`, `generateCartInquiryText()`, `recordClick()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-PUB-WA-001`** | Positif | Floating Action Button Redirection di Beranda Publik | Pengguna membuka beranda publik | Klik tombol Floating WA di pojok kanan bawah | Mengarahkan ke rute `/wa-redirect?source=floating_home` lalu diteruskan ke `https://wa.me/62811xxxxxxx?text=...` dengan salam pembuka formal PT. Panca Bima Bergawa. | Not Executed |
| **`TC-PUB-WA-002`** | Positif | Inkuiri WhatsApp Spesifik dari Halaman Detail Produk | Pengguna membuka produk `SCH-MCB-16A` | Klik tombol "Tanya via WhatsApp" pada detail SKU | WhatsApp terbuka dengan pesan terformat memuat: Nama Produk, SKU, dan URL tautan produk di web PBB. | Not Executed |
| **`TC-PUB-WA-003`** | Positif | Inkuiri WhatsApp dari Keranjang RFQ Multi-Item | Keranjang RFQ terisi 3 item produk | Klik tombol "Kirim RFQ via WhatsApp" di drawer | WhatsApp terbuka dengan daftar ringkas 3 SKU beserta kuantitas masing-masing dalam format teks rapi dan terstruktur. | Not Executed |
| **`TC-PUB-WA-004`** | Positif | Pencatatan Telemetri Klik Inkuiri WhatsApp | Pengguna mengklik tombol WA | Data klik: IP, User-Agent, referer, target SKU | Sistem mencatat 1 baris log pada tabel `telemetry_logs` tanpa menghambat (*non-blocking asynchronous / fast write*) proses pengalihan URL. | Not Executed |
| **`TC-PUB-WA-005`** | Positif | Deep Linking WhatsApp pada Perangkat Mobile Smartphone | Pengguna mengakses web via browser smartphone | Tap tombol WhatsApp | Perangkat langsung membuka aplikasi WhatsApp native (skema URI `whatsapp://send` atau `https://wa.me`), bukan browser web. | Not Executed |
| **`TC-PUB-WA-006`** | Positif | Pengalihan WhatsApp Web pada Perangkat Desktop | Pengguna mengakses web via browser desktop | Klik tombol WhatsApp | Browser membuka tab baru mengarah ke `https://web.whatsapp.com/send?...` dengan pesan sudah terisi di kotak obrolan. | Not Executed |
| **`TC-PUB-WA-007`** | Negatif | Penanganan Kegagalan Konfigurasi Nomor WhatsApp | Nilai `WHATSAPP_SALES_NUMBER` di `.env` kosong | Pengguna mengklik tombol WA | Controller menangani ketiadaan nomor dengan mengarahkan pengguna ke halaman kontak resmi `/kontak` dengan pesan informasi formal. | Not Executed |
| **`TC-PUB-WA-008`** | Negatif | Inkuiri WhatsApp dari Keranjang RFQ yang Kosong | Keranjang RFQ dalam keadaan 0 item | Akses langsung `/wa-redirect?source=rfq_cart` | Sistem mendeteksi keranjang kosong dan mengarahkan ke nomor sales dengan salam pembuka umum katalog. | Not Executed |
| **`TC-PUB-WA-009`** | Batas Nilai | Proteksi Batas Panjang URL (*URL Length Constraint*) | Keranjang RFQ memuat > 30 item produk | Klik "Kirim RFQ via WhatsApp" | `WhatsAppMessageGeneratorService` membatasi ringkasan teks agar tidak melebihi 1500 karakter dan menyertakan rujukan ringkas agar URL tidak error di browser. | Not Executed |
| **`TC-PUB-WA-010`** | Batas Nilai | Penanganan Karakter Multibyte & Nama Perusahaan Khusus | Input nama perusahaan memuat karakter `&`, `#`, `%`, `+` | Nama Klien: `PT. Sumber & Berkah #1 + Partners` | Teks dienkode penuh menggunakan standar RFC 3986 (`rawurlencode`), teks diterima utuh di aplikasi WhatsApp tanpa terpotong atau rusak. | Not Executed |
| **`TC-PUB-WA-011`** | Keamanan | Pencegahan Kerentanan Open Redirect Vulnerability | Penyerang memanipulasi parameter query redirect | Request: `/wa-redirect?target=https://evil-site.com` | Sistem memvalidasi URL tujuan hanya boleh mengarah ke whitelist domain resmi WhatsApp (`wa.me` dan `api.whatsapp.com`), parameter berbahaya diabaikan. | Not Executed |
| **`TC-PUB-WA-012`** | Keamanan | Rate Limiting Pencatatan Telemetri Log (*Anti-Log Flooding*) | Bot melakukan spam klik tombol WA bertubi-tubi | 50 request klik dalam waktu 10 detik dari IP sama | Rate limiter membatasi pencatatan log maksimal 10 log per menit per IP, mencegah lonjakan ukuran tabel `telemetry_logs`. | Not Executed |
| **`TC-PUB-WA-013`** | UX & Aksesibilitas | Aksesibilitas Tombol Floating WA Keyboard Nav | Halaman beranda dimuat | Fokus ke tombol floating WA via tombol `Tab` keyboard | Tombol floating WA memiliki focus ring kontras tinggi, `aria-label="Hubungi Sales PT. Panca Bima Bergawa via WhatsApp"`, dan dapat diaktifkan via tombol `Enter`. | Not Executed |
| **`TC-PUB-WA-014`** | Zero Emoji | Penegakan Larangan Emoji pada Template Pesan WhatsApp | Generate pesan otomatis | Inspeksi string teks output generator | Pesan WhatsApp menggunakan penomoran teks rapi dan tanda baca formal standar, 100% bebas dari icon emoji. | Not Executed |
| **`TC-PUB-WA-015`** | Performa | Waktu Respons Pengalihan (*Redirect Latency*) | Pengguna mengklik tombol | Eksekusi controller redirection | Waktu dari klik hingga penerimaan respons HTTP 302 Redirect < 150ms pada koneksi normal. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-PUB-WA-002 (Inkuiri Produk Spesifik via WhatsApp)

1. **Langkah 1:** Buka `https://bimabergawa.com/katalog/schneider-mcb-16a-domae`.
2. **Langkah 2:** Klik tombol **"Tanya Stok via WhatsApp"**.
3. **Verifikasi Teknis:**
   - Permintaan dialihkan melalui `WhatsAppRedirectController::redirect()`.
   - Browser membuka tab baru ke domain resmi `api.whatsapp.com` atau `wa.me`.
   - Isi pesan terenkode memuat:
     ```
     Halo Tim Sales PT. Panca Bima Bergawa,
     Saya ingin menanyakan ketersediaan dan penawaran harga untuk produk:
     - Nama: Schneider MCB Domae 16A 1P
     - SKU: SCH-MCB-16A
     - Tautan: https://bimabergawa.com/katalog/schneider-mcb-16a-domae
     
     Mohon informasinya. Terima kasih.
     ```
   - Tabel `telemetry_logs` di database bertambah 1 baris dengan `event_type = 'wa_product_click'` dan `target_sku = 'SCH-MCB-16A'`.

### 3.2 Prosedur Uji TC-PUB-WA-011 (Uji Pencegahan Open Redirect)

1. **Langkah 1:** Kirimkan request manipulasi URL target:
   ```bash
   curl -I "https://bimabergawa.com/wa-redirect?url=https://attacker-domain.com"
   ```
2. **Verifikasi Teknis:**
   - Header `Location` respon harus tetap mengarah ke domain resmi `https://wa.me/62...` milik PT. Panca Bima Bergawa.
   - Sistem menolak pengalihan ke domain eksternal selain WhatsApp.
