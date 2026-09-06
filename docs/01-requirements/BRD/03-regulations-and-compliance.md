# 03. Regulasi, Kepatuhan Pajak & Standar Hukum B2B
## Business Requirement Document (BRD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-BRD-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Tim Legal & Tax Compliance | Draf Awal | Pemetaan regulasi PPN 11% (UU HPP) & kepatuhan Faktur Pajak |
| `1.1.0` | 03 Sep 2026 | Corporate Legal Counsel | Ditinjau | Penyesuaian kepatuhan privasi data pelanggan (UU PDP No. 27/2022) |
| `1.2.0` | 05 Sep 2026 | Finance & Compliance Lead | Disetujui | Aturan bukti potong PPh 21/23 komisi agen freelance & slip transfer bank |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Regulasi Perpajakan Indonesia (Taxation Compliance)

Dalam transaksi perdagangan barang industri dan perlengkapan teknik B2B di Indonesia, seluruh penerbitan dokumen komersial formal wajib mematuhi regulasi perpajakan yang berlaku.

```
+─────────────────────────────────────────────────────────────────────────────+
|                         STANDAR PERHITUNGAN PAJAK B2B                       |
+─────────────────────────────────────────────────────────────────────────────+
  Subtotal Produk           : Rp 100.000.000,- (Jumlah Harga Satuan x Qty)
  Potongan Diskon Komersial : Rp  10.000.000,- (-)
  ─────────────────────────────────────────────
  Dasar Pengenaan Pajak (DPP): Rp  90.000.000,-
  PPN (Tarif Resmi 11%)     : Rp   9.900.000,- (+) (DPP x 11%)
  ─────────────────────────────────────────────
  Grand Total Tagihan       : Rp  99.900.000,- (DPP + PPN)
```

### 1.1 Kepatuhan UU Harmonisasi Peraturan Perpajakan (UU HPP No. 7/2021)
1. **Transparansi DPP & PPN:** Setiap Surat Penawaran Resmi (*Quotation*) dan Sales Order yang diterbitkan sistem wajib memisahkan secara eksplisit antara nilai **Subtotal**, **Diskon**, **Dasar Pengenaan Pajak (DPP)**, dan **Nominal PPN**.
2. **Fleksibilitas Tarif Pajak:** Konfigurasi persentase PPN di dalam sistem (`tax_percentage`) didesain dinamis (default `11.00%`) guna mengantisipasi penyesuaian tarif PPN di masa mendatang sesuai kebijakan Kementerian Keuangan.
3. **Kesiapan Faktur Pajak:** Data profil perusahaan pemohon (Nama Badan Usaha, NPWP/Identitas PIC, Alamat Domisili) yang masuk melalui form RFQ harus tersimpan rapi untuk mempermudah penerbitan e-Faktur oleh bagian keuangan.

---

## 2. Standar Hukum Kontrak & Perdagangan Komersial (Commercial Law)

Dokumen penawaran harga (*Quotation*) dan konfirmasi pesanan (*Sales Order*) memiliki kedudukan hukum penting dalam transaksi perdata dagang (KUHPerdata Buku III tentang Perikatan).

### 2.1 Klausul Wajib pada Berkas Surat Penawaran Resmi (Quotation)
Untuk memastikan keabsahan dokumen penawaran dan melindungi perusahaan dari fluktuasi harga prinsipal atau depresiasi mata uang:
1. **Nomor Dokumen Tunggal:** Format unik `QUO-YYYYMM-XXXX` yang tidak boleh terduplikasi.
2. **Masa Berlaku Penawaran (*Validity Period*):** Default `14 hari kalender`. Penawaran otomatis gugur atau harus dikonfirmasi ulang jika melewati batas waktu tersebut.
3. **Syarat Pembayaran (*Terms of Payment / TOP*):**
   - *Cash Before Delivery (CBD)* / Uang Muka (*Down Payment* 50% + Pelunasan sebelum kirim).
   - *Term of Payment 30/60 Hari* setelah barang diterima (khusus klien korporat dengan fasilitas kredit yang telah disetujui).
4. **Syarat Penyerahan Barang (*Incoterms / Delivery Terms*):**
   - *Franco Jakarta / Franco Proyek:* Biaya pengiriman dan risiko perjalanan ditanggung perusahaan hingga sampai di lokasi klien.
   - *Loco Gudang:* Pengambilan barang dilakukan sendiri oleh klien di gudang perusahaan.

### 2.2 Hubungan Quotation dan Purchase Order (PO)
- Surat Penawaran resmi berstatus *Offer*.
- Ketika klien mengirimkan Purchase Order (PO) fisik resmi mengacu pada nomor Quotation tersebut, terjadi perikatan hukum yang sah (*Deal / Acceptance*), dan sistem akan mengunci data penawaran untuk mencegah perubahan sepihak.

---

## 3. Kepatuhan Perlindungan Data Pribadi (UU PDP No. 27/2022)

Sistem mengelola data identitas *person-in-charge* (PIC) pengadaan dari berbagai korporasi klien.

1. **Prinsip Kerahasiaan Data:** Nomor telepon/WhatsApp, alamat email, dan identitas proyek klien tidak boleh dipublikasikan atau dapat diakses oleh publik luar.
2. **Isolasi Subdomain ERP:** Panel admin di `app.domain.com` wajib menyematkan tag HTTP Header `X-Robots-Tag: noindex, nofollow, noarchive` dan `Disallow: /` pada file robots.txt guna menjamin bot mesin pencari Google tidak pernah mengindeks data internal klien.
3. **Penyimpanan Kredensial:** Seluruh kata sandi staf internal dienkripsi menggunakan standar industri Bcrypt (*cost factor* 12) dan tidak ada penyimpanan *plain-text password*.

---

## 4. Kepatuhan Perjanjian Distributor Resmi Prinsipal (Principal Agreement)

Sebagai distributor resmi terdaftar untuk brand prinsipal (Ebara, Schneider Electric, Danfoss, dll.):
1. **Orisinalitas Nomor Model:** Sistem wajib menampilkan part number/kode model asli pabrikan tanpa manipulasi untuk menjamin kepastian kecocokan barang bagi klien.
2. **Ketersediaan Lembar Data Teknis (*Datasheet*):** Sistem memfasilitasi pengunduhan brosur PDF resmi langsung dari prinsipal.
3. **Atribusi Brand Resmi:** Menampilkan penanda khusus (*Official Distributor Badge*) pada halaman profil brand yang memiliki surat penunjukan keagenan resmi.
