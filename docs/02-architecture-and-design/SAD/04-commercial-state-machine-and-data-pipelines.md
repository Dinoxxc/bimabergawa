# 04. State Machine Alur Komersial & Pipeline Dokumen
## System Architecture Document (SAD)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SAD-04` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Enterprise Architect | Draf Awal | Definisi diagram state machine Inquiry -> Quotation -> SO -> DO |
| `1.1.0` | 03 Sep 2026 | Commercial Systems Engineer | Ditinjau | Penambahan state partial fulfillment pada Delivery Order (Surat Jalan) |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Validasi transisi status otomatis & trigger webhooks notifikasi email |
| `1.3.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan kepatuhan UU HPP PPN snapshot, toleransi pencarian SKU, & eliminasi total emoji |

---

## 1. Diagram State Machine Alur Komersial Menyeluruh

Sistem mengelola siklus hidup transaksi B2B dari tahap penangkapan prospek hingga penyelesaian pengiriman barang dan pencairan komisi:

```mermaid
stateDiagram-v2
    direction TB

    state Fase_1_Penangkapan_Prospek {
        [*] --> Ingestion_Dual_Track: Single RFQ Modal / Bulk Cart / Offline Lead
        Ingestion_Dual_Track --> Inquiry_New: Terbit RFQ-YYYYMM-XXXX
        Inquiry_New --> Inquiry_Contacted: Sales Hubungi via WhatsApp/Telepon
    }

    state Fase_2_Penyusunan_Penawaran {
        Inquiry_Contacted --> Quotation_Draft: 1-Click Convert (QUO-YYYYMM-XXXX)
        Quotation_Draft --> Kalkulasi_Margin: Input Harga Satuan, Diskon & HPP
        
        state Margin_Gate <<choice>>
        Kalkulasi_Margin --> Margin_Gate
        Margin_Gate --> Otorisasi_Direksi: Diskon > 10% ATAU Margin HPP < 15%
        Margin_Gate --> Siap_Terbit: Margin Aman (≥ 15%) & Diskon Normal (≤ 10%)
        
        Otorisasi_Direksi --> Siap_Terbit: Disetujui Direksi (Approval Log)
        Otorisasi_Direksi --> Quotation_Draft: Ditolak Direksi (Revisi Harga)

        Siap_Terbit --> Quotation_Sent: Generate PDF Resmi & Kirim via WA/Email
        Quotation_Sent --> Quotation_Draft: Klien Minta Revisi Teknis/Harga (Rev +1)
        Quotation_Sent --> Inquiry_Lost: Negosiasi Gagal / Batal
        Quotation_Sent --> Quotation_Approved: Klien Rilis Purchase Order (PO) Resmi
    }

    state Fase_3_Sales_Order_dan_Pengiriman {
        Quotation_Approved --> SalesOrder_Issued: Input Nomor PO Fisik (SO-YYYYMM-XXXX)
        SalesOrder_Issued --> Quotation_Locked: Lock Dokumen Quote (Read-Only)
        SalesOrder_Issued --> Komisi_Payable: Status Hak Komisi Agen = Payable
        
        SalesOrder_Issued --> Proses_Logistik: Barang Ready / Indent Prinsipal
        Proses_Logistik --> Partial_DO_Issued: Terbitkan DO-YYYYMM-XXXX/01 (Tanpa Harga)
        Partial_DO_Issued --> Ekspedisi_Shipped: Input Vendor Ekspedisi, Resi AWB & URL Tracking
        Ekspedisi_Shipped --> Order_Completed: Barang Diterima Gudang Klien (Upload POD)
    }

    state Fase_4_Pencairan_Komisi_Mitra {
        Order_Completed --> Komisi_Paid: Super Admin Transfer & Upload Slip Bank
    }

    Inquiry_Lost --> [*]
    Komisi_Paid --> [*]
```

---

## 2. Kalkulator Margin Laba HPP & Otorisasi Direksi

Untuk menjaga kesehatan finansial perusahaan dari risiko perang harga dan fluktuasi kurs prinsipal:

$$\text{DPP} = \text{Subtotal} - \text{Diskon}$$
$$\text{Total HPP (Modal)} = \sum (\text{cost\_price} \times \text{kuantiti})$$
$$\text{Gross Profit Margin \%} = \frac{\text{DPP} - \text{Total HPP}}{\text{DPP}} \times 100\%$$

```mermaid
graph TD
    A[Sales Menyusun Draft Penawaran] --> B[Sistem Menghitung Live Margin HPP]
    B --> C{Margin < 15% atau Diskon > 10%?}
    C -->|Ya| D[Form Terkunci: Butuh Approval Direksi]
    D --> E[Direksi Meninjau di ERP & Memberikan Approval]
    E --> F[Status Quotation Menjadi Approved / Siap Terbit]
    C -->|Tidak| F
    F --> G[Render PDF Resmi Ber-Kop Surat Tanpa Watermark Draft]
```

---

## 3. Mesin Skema Pembayaran Fleksibel (*Flexible Payment Terms Engine*)

Sistem mendukung fleksibilitas penuh untuk transaksi langsung maupun proyek bertahap:

```
                                  [ PILIHAN SKEMA PEMBAYARAN ]
                                                │
                ┌───────────────────────────────┴───────────────────────────────┐
                ▼                                                               ▼
    [ TIPE A: PEMBAYARAN LANGSUNG 100% ]                         [ TIPE B: TERMIN / PER FASE / BERTAHAP ]
                │                                                               │
  ┌─────────────┼─────────────┐                                   ┌─────────────┼─────────────┐
  ▼             ▼             ▼                                   ▼             ▼             ▼
[ 100% CBD ]  [ 100% COD ]  [ 100% TOP Net X ]                  [ 2 Fase ]    [ 3 Fase ]    [ Custom N-Fase ]
(Bayar Lunas  (Bayar Lunas  (Bayar Lunas Net 14/                (DP 50% +     (DP 30% +     (Admin bebas
 di Awal       saat Barang   30/60 Hari setelah                  Pelunasan     50% Kirim +   mengatur berapa pun
 sebelum       Tiba di       Barang & Invoice                    sebelum       20% Retensi   fase & persentase,
 Kirim/Indent) Gudang)       Diterima)                           Kirim)        Komisioning)  total = 100%)
```

### Validasi Integritas Termin:
Jika memilih Tipe B (Multi-Termin), sistem menjalankan validasi:
$$\sum_{i=1}^{N} \text{percentage}_i = 100.00\%$$

---

## 4. Mesin Dokumen PDF Ber-Watermark Dinamis (*DomPDF Layer*)

Generator PDF (`Barryvdh\DomPDF\Facade\Pdf`) menyematkan lapisan keamanan visual otomatis:

| Status Dokumen | Tampilan Watermark Diagonal | Elemen Kop Surat & Tanda Tangan | Kegunaan Bisnis |
|:---|:---|:---|:---|
| `draft` | **"DRAFT - NOT OFFICIAL"** (Opasitas 15%) | Tanpa tanda tangan PIC | Mencegah draf diedit disalahgunakan sebelum disetujui internal. |
| `sent` / `approved` | **BERSIH (Tanpa Watermark)** | Kop Surat Resmi, TTD Digital & Rekening Bank | Berkas penawaran resmi berkuasa hukum yang dikirim ke klien. |
| `expired` | **"EXPIRED / VOID"** (Merah Pudar) | Dicap kedaluwarsa | Menghindari klaim harga lama saat kurs mata uang telah berubah. |

---

## 5. Arsitektur Surat Jalan Ekspedisi & Partial Delivery Order

```
[ SALES ORDER (SO-202609-0012) ] - Total: 15 Unit Pompa Ebara
                      │
       ┌──────────────┴──────────────┐
       ▼                             ▼
[ PENGIRIMAN TAHAP 1 ]         [ PENGIRIMAN TAHAP 2 ]
DO-202609-0012/01 (10 Unit)    DO-202609-0012/02 (5 Unit)
- Ekspedisi: Baraka Cargo      - Ekspedisi: Indah Logistik
- Resi: BRK-881290             - Resi: IND-992011
- Resi Tracking URL            - Resi Tracking URL
- 100% TANPA HARGA             - 100% TANPA HARGA
- Kolom Stempel Gudang Pabrik  - Kolom Stempel Gudang Pabrik
```

1. **Privasi Finansial Mutlak:** Berkas PDF Surat Jalan dicetak murni memuat: *Nama Ekspedisi, Nomor Resi/AWB, Estimasi Koli & Berat, Rincian Barang/Model, Alamat Pabrik Tujuan, PIC Lapangan, Catatan Penanganan, dan Kolom Tanda Tangan/Stempel Gudang*.
2. **Pelacakan Publik (`/lacak-rfq`):** Calon pembeli/klien dapat memantau status pesanan dan menekan tombol *"Lacak di Website Resmi Ekspedisi"* untuk membuka portal cek resi kurir terkait secara instan.
