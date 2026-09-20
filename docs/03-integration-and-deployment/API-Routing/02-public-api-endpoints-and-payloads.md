# 02. Spesifikasi Endpoint API Publik & Skema Payload JSON
## API & Routing Specification Suite (API-Routing)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-API-02` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Backend Lead | Draf Awal | Definisi skema JSON submit RFQ massal |
| `1.1.0` | 03 Sep 2026 | API Architect | Ditinjau | Penambahan skema endpoint parser BOQ & pelacakan pesanan |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan skema error 422 & batasan rate limiting per IP |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan dokumen mandiri payload API, validasi data, & zero emoji |
| `1.4.0` | 20 Sep 2026 | Security & API Architect | Disetujui | Penambahan skema Two-Key Verification (doc_number + contact_verification), Signed URL, dan rute unduh dokumen privat |

---

## 1. Standar Konvensi Header & Status Code API

Seluruh endpoint API publik mengadopsi standar respon RESTful berbasis JSON:

### 1.1 Standar Request & Response Headers
```http
Content-Type: application/json
Accept: application/json
X-CSRF-TOKEN: [csrf_token_string]
```

### 1.2 Standar HTTP Status Codes
| HTTP Status | Arti / Kondisi | Format Body Respon |
| :--- | :--- | :--- |
| `200 OK` | Permintaan berhasil diproses (Query/Search/Parse) | `{"success": true, "data": ...}` |
| `201 Created` | Sumber daya baru berhasil dibuat (Inquiry/Lead/Mitra) | `{"success": true, "message": "...", "data": ...}` |
| `422 Unprocessable`| Validasi data formulir gagal | `{"success": false, "message": "...", "errors": {...}}` |
| `429 Too Many Req`| Melebihi kuota request rate-limiter | `{"success": false, "message": "Terlalu banyak permintaan."}` |
| `500 Server Error` | Terjadi kegagalan internal sistem | `{"success": false, "message": "Terjadi kesalahan internal."}` |

---

## 2. Rincian Endpoint & Payload Data

### 2.1 Submit Permintaan Penawaran Massal (`POST /rfq/submit`)
- **Fungsi:** Mengirim seluruh item dalam keranjang RFQ ke antrean prospek CRM ERP.
- **Rate Limit:** 10 request / menit per IP (`throttle:10,1`).

**Payload Request (JSON):**
```json
{
  "customer_name": "Ir. Hendra Gunawan",
  "company_name": "PT. Petro Perkasa Indonesia",
  "company_npwp": "02.889.123.4-055.000",
  "phone_wa": "081198765432",
  "email": "hendra@petroperkasa.co.id",
  "project_location": "Kawasan Industri Cilegon, Banten",
  "payment_term_preference": "milestone",
  "notes": "Mohon penawaran dengan sertifikat uji pabrik (Factory Test Report).",
  "items": [
    {
      "product_id": 104,
      "quantity": 3,
      "unit_type": "Unit",
      "special_requirements": "Flange DIN PN16 Stainless Steel SS304"
    },
    {
      "product_id": 210,
      "quantity": 2,
      "unit_type": "Unit",
      "special_requirements": "Include Braking Resistor"
    }
  ],
  "utm_source": "google_ads",
  "utm_medium": "search",
  "utm_campaign": "pompa_ebara_b2b"
}
```

**Respon Sukses (201 Created):**
```json
{
  "success": true,
  "status_code": 201,
  "message": "Permintaan penawaran resmi berhasil didaftarkan.",
  "data": {
    "inquiry_number": "PBB/INQ/2026/09/0021",
    "total_items": 2,
    "company_name": "PT. Petro Perkasa Indonesia",
    "submitted_at": "2026-09-05T18:45:00+07:00",
    "tracking_url": "https://bimabergawa.com/lacak-rfq?ref=PBB/INQ/2026/09/0021"
  }
}
```

---

### 2.2 Submit Permintaan Penawaran Cepat 1 SKU (`POST /rfq/single`)
- **Fungsi:** Form modal "Tanya Penawaran" langsung pada halaman detail produk tanpa membuka drawer keranjang.
- **Rate Limit:** 10 request / menit per IP (`throttle:10,1`).

**Payload Request (JSON):**
```json
{
  "product_id": 104,
  "customer_name": "Budi Santoso",
  "company_name": "CV. Teknik Mandiri",
  "phone_wa": "081234567890",
  "email": "budi@teknikmandiri.com",
  "quantity": 1,
  "unit_type": "Unit",
  "notes": "Dibutuhkan segera untuk penggantian unit yang rusak di pabrik."
}
```

**Respon Sukses (201 Created):**
```json
{
  "success": true,
  "status_code": 201,
  "message": "Permintaan harga produk berhasil dikirim. Sales PIC kami akan segera menghubungi Anda via WhatsApp.",
  "data": {
    "inquiry_number": "PBB/INQ/2026/09/0022"
  }
}
```

---

### 2.3 Validasi & Parser Part Number Massal (`POST /api/bulk-sku/parse`)
- **Fungsi:** Memecah string daftar BOQ/Excel menjadi baris SKU terverifikasi dengan mencocokkan nomor model di database.
- **Rate Limit:** 20 request / menit per IP (`throttle:20,1`).

**Payload Request (JSON):**
```json
{
  "raw_text": "FSHA 65-50-200, 2, Flange ANSI 150\nATV320U55N4B, 4, 380V\nE2E-X7D1-N, 10, Kabel 5M"
}
```

**Respon Sukses (200 OK):**
```json
{
  "success": true,
  "total_parsed": 3,
  "matched_count": 3,
  "unmatched_count": 0,
  "results": [
    {
      "input_sku": "FSHA 65-50-200",
      "is_matched": true,
      "product": {
        "id": 104,
        "model_number": "FSHA 65-50-200",
        "brand": "Ebara",
        "category": "End Suction Pump",
        "stock_status": "ready"
      },
      "parsed_quantity": 2,
      "parsed_note": "Flange ANSI 150"
    },
    {
      "input_sku": "ATV320U55N4B",
      "is_matched": true,
      "product": {
        "id": 210,
        "model_number": "ATV320U55N4B",
        "brand": "Schneider Electric",
        "category": "Inverter VFD",
        "stock_status": "ready"
      },
      "parsed_quantity": 4,
      "parsed_note": "380V"
    },
    {
      "input_sku": "E2E-X7D1-N",
      "is_matched": true,
      "product": {
        "id": 305,
        "model_number": "E2E-X7D1-N",
        "brand": "Omron",
        "category": "Proximity Sensor",
        "stock_status": "ready"
      },
      "parsed_quantity": 10,
      "parsed_note": "Kabel 5M"
    }
  ]
}
```

---

### 2.4 Pelacakan Status Pengadaan Mandiri (`POST /lacak-rfq/search`)
- **Fungsi:** Mengambil status real-time pesanan pengadaan dan nomor resi ekspedisi untuk klien publik dengan verifikasi dua kunci (Two-Key Verification) guna mencegah serangan IDOR dan enumerasi nomor sekuensial.
- **Rate Limit:** 5 request / menit per IP (`throttle:5,1`) dengan challenge CAPTCHA setelah 3 kali kegagalan.
- **Akses Alternatif:** Tautan langsung via email menggunakan Cryptographic Temporary Signed URL (`GET /lacak-rfq?ref=PBB/INQ/2026/09/0021&expires=...&signature=...`).

**Payload Request (JSON):**
```json
{
  "doc_number": "PBB/INQ/2026/09/0021",
  "contact_verification": "081234567890"
}
```
*Catatan:* `contact_verification` dapat berupa Nomor WhatsApp terdaftar (misal: `081234567890`) atau Alamat Email terdaftar (misal: `purchasing@petroperkasa.co.id`).

**Respon Sukses (200 OK):**
```json
{
  "success": true,
  "data": {
    "inquiry_number": "PBB/INQ/2026/09/0021",
    "company_name": "PT. Petro Perkasa Indonesia",
    "pic_masked": "Ahm** Fa*** (0812-****-7766)",
    "project_location": "Cilegon, Banten",
    "status": "deal",
    "progress_step": 4,
    "total_steps": 5,
    "quotation": {
      "quotation_number": "PBB/QUO/2026/09/0018",
      "pdf_download_url": "https://bimabergawa.com/documents/download/quotation/PBB-QUO-2026-09-0018?token=8f9a2b...&expires=1726913245"
    },
    "sales_order": {
      "so_number": "PBB/SOR/2026/09/0015",
      "customer_po": "PO-PPI-2026-09"
    },
    "deliveries": [
      {
        "do_number": "PBB/DOR/2026/09/0018/01",
        "sent_date": "2026-09-05",
        "courier_name": "Dakota Cargo",
        "tracking_number": "DKT-998822110",
        "tracking_url": "https://dakotacargo.co.id/track?no=DKT-998822110",
        "items_shipped": [
          { "model_number": "FSHA 65-50-200", "quantity": 3 }
        ]
      }
    ]
  }
}
```

**Respon Gagal / IDOR Blocked (404 Not Found / 422 Unprocessable Entity):**
```json
{
  "success": false,
  "message": "Kombinasi nomor dokumen dan kontak verifikasi tidak ditemukan atau tidak cocok."
}
```

---

### 2.4b Pengunduhan Dokumen Aman Privat (`GET /documents/download/{type}/{token}`)
- **Fungsi:** Mengalirkan (*stream/download*) berkas PDF penawaran resmi dari private storage (`storage/app/private/documents/`) setelah memvalidasi token dan signature kriptografis.
- **Masa Kedaluwarsa Token:** 15 menit (*One-Time / Short-Lived Access Token*).
- **Proteksi:** Memblokir akses langsung publik, mencegah bot search engine mengindeks penawaran harga, dan mengembalikan HTTP 403 Forbidden jika signature tidak valid.

---

### 2.5 Pengajuan Kemitraan Freelance Sales (`POST /gabung-mitra/apply`)
- **Fungsi:** Mendaftarkan calon agen lepas dan mengunggah berkas KTP serta rekening bank.
- **Content-Type:** `multipart/form-data`.
- **Rate Limit:** 5 request / menit per IP (`throttle:5,1`).

**Field Form Data:**
- `full_name` (String, Wajib, Max 100 char)
- `email` (Email, Wajib, Unique)
- `phone_wa` (String, Wajib, Min 10 char)
- `bank_name` (String, Wajib, e.g. `BCA`)
- `bank_account_number` (String, Wajib)
- `bank_account_holder` (String, Wajib)
- `target_industries` (Array String, e.g. `["Pabrik Makanan", "Kontraktor ME"]`)
- `ktp_file` (File PDF/JPG/PNG, Max 2MB, Wajib)

---

## 3. Format Respon Kesalahan Validasi (422 Unprocessable Entity)

```json
{
  "success": false,
  "message": "Data formulir yang Anda masukkan tidak valid.",
  "errors": {
    "phone_wa": [
      "Nomor WhatsApp wajib diisi dengan format nomor telepon yang valid."
    ],
    "items": [
      "Keranjang permintaan penawaran tidak boleh kosong."
    ]
  }
}
```
