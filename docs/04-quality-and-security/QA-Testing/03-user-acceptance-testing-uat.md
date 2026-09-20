# 03. Skenario UAT End-to-End & Berita Acara Penerimaan Sistem
## Quality Assurance & Testing Suite (QA-Testing)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-QA-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Business Analyst | Draf Awal | Skenario UAT alur penawaran harga dasar |
| `1.1.0` | 03 Sep 2026 | Product Owner | Ditinjau | Penambahan skenario UAT agen freelance & pelacakan resi |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan matriks sign-off 5 persona pengguna |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Pemisahan berkas mandiri skenario UAT & zero emoji |

---

## 1. Lingkup Pengujian Penerimaan Pengguna (UAT Scope by Persona)

Pengujian UAT mencakup seluruh perjalanan pengguna (*End-to-End Journey*) berdasarkan 5 persona:

```
[1. Procurement Buyer] ──> [2. Sales PIC ERP] ──> [3. Finance & Direksi] ──> [4. Logistik Ekspedisi] ──> [5. Mitra Agen]
(Submit BOQ / RFQ)        (Quotation & Margin)    (Approval & Pelunasan)      (Partial DO & Resi)       (Slip Komisi Bank)
```

---

## 2. Matriks Skenario UAT End-to-End

| ID UAT | Persona Pengguna | Langkah Skenario Pengujian | Hasil Aktual yang Divalidasi | Status |
| :--- | :--- | :--- | :--- | :---: |
| **UAT-01** | Insinyur Pabrik / Buyer | Membuka katalog, menempel 5 SKU di modal *Bulk SKU Parser*, dan mengirim form RFQ. | Berhasil mendaftarkan nomor `INQ-202609-XXXX` dan data tersimpan di keranjang. | **Lolos** |
| **UAT-02** | Buyer Publik | Membuka `/lacak-rfq`, memasukkan nomor RFQ, dan memantau status pesanan. | Menampilkan progres tahapan pengadaan dan tombol unduh berkas PDF penawaran. | **Lolos** |
| **UAT-03** | Sales PIC Internal | Membuka Kanban ERP, mengklaim prospek masuk, dan membuat Surat Penawaran. | Kalkulator Live Margin menghitung HPP otomatis dan mencetak PDF Kop Surat resmi. | **Lolos** |
| **UAT-04** | Sales PIC Internal | Mengklik tombol *"Kirim Quotation via WhatsApp"* di panel Filament. | Membuka WhatsApp Web dengan pesan pengantar resmi dan tautan berkas PDF. | **Lolos** |
| **UAT-05** | Direksi & Finance | Menerima pengajuan penawaran dengan margin rendah ($<15\%$). | Sistem memblokir penerbitan sampai Direksi memberikan *1-Click Approval*. | **Lolos** |
| **UAT-06** | Petugas Logistik | Menerbitkan Surat Jalan parsial (2 dari 4 unit) dan menginput resi Dakota Cargo. | Cetak Surat Jalan fisik 100% tanpa harga, dan resi langsung muncul di `/lacak-rfq`. | **Lolos** |
| **UAT-07** | Freelance Sales | Mendaftar di `/gabung-mitra`, login ke `/mitra`, dan memantau komisi. | Dashboard menampilkan omzet pribadi terisolasi dan tombol unduh bukti transfer bank. | **Lolos** |

---

## 3. Format Berita Acara Penerimaan Sistem (Sign-off Template)

```
================================================================================
                    BERITA ACARA PENGUJIAN PENERIMAAN PENGGUNA
                        USER ACCEPTANCE TESTING (UAT)
================================================================================

Nama Proyek    : B2B Multi-Brand Product Showcase & Internal Commercial ERP
Entitas Usaha  : PT. Panca Bima Bergawa
Tanggal Uji    : September 2026
Hasil Evaluasi : Seluruh 7 Skenario UAT dinyatakan MEMENUHI SYARAT (ACCEPTED)

Perwakilan Tim Penguji:

1. Perwakilan Komersial / Sales Lead     : [ Ir. Budi Santoso ]  (Tanda Tangan: _________)
2. Perwakilan Finansial & Operasional    : [ Hendra Gunawan ]    (Tanda Tangan: _________)
3. Perwakilan Logistik & Gudang         : [ Dian Prasetya ]     (Tanda Tangan: _________)
4. Principal System Architect PBB        : [ Lead Architect ]    (Tanda Tangan: _________)
================================================================================
```
