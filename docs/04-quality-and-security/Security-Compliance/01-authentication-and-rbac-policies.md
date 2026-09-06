# 01. Kebijakan Otentikasi, Keamanan Sesi, & Otorisasi Spatie RBAC
## Security Policy & Regulatory Compliance Suite (Security-Compliance)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-SEC-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Security Engineer | Draf Awal | Definisi standar hashing password & sesi |
| `1.1.0` | 03 Sep 2026 | IAM Specialist | Ditinjau | Penambahan protokol Passwordless OTP & isolasi role Spatie |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan tenant scope agen freelance & session fixation guard |
| `1.3.0` | 05 Sep 2026 | Principal Security Architect | Disetujui | Pemisahan berkas mandiri kebijakan otentikasi & zero emoji |

---

## 1. Protokol Otentikasi Passwordless Email OTP

Untuk mengeliminasi risiko kebocoran kata sandi dan *credential stuffing*, panel internal ERP menerapkan otentikasi tanpa sandi (*Passwordless Email OTP*):

1. **Pembangkitan Token OTP:**
   - Menggunakan generator angka acak *CSPRNG* (`random_int(100000, 999999)`).
   - Kode di-hash menggunakan SHA-256 sebelum disimpan pada cache dengan masa aktif (*Time-to-Live*) tepat **5 menit (300 detik)**.
2. **Penghangusan Token (One-Time Burn):**
   - Kode OTP langsung dihapus dari sistem begitu berhasil diverifikasi untuk mencegah *replay attack*.
3. **Pencegahan Brute-Force & Perlindungan SMTP:**
   - Dibatasi maksimal 3 percobaan permintaan OTP per 10 menit per email/IP (`throttle:3,10`).
   - Terdapat jeda hitung mundur (*cooldown*) 60 detik di antarmuka sebelum tombol kirim ulang aktif.

---

## 2. Keamanan Sesi Pengguna (Session Security Standards)

Seluruh sesi web dan ERP dikelola dengan konfigurasi keamanan ketat pada `config/session.php`:

| Parameter Konfigurasi | Nilai Produksi | Tujuan Pengamanan |
| :--- | :--- | :--- |
| `lifetime` | `120` menit | Mengunci sesi secara otomatis jika pengguna tidak aktif |
| `expire_on_close` | `true` | Menghapus sesi saat browser ditutup |
| `http_only` | `true` | Mencegah pembacaan cookie sesi oleh skrip berbahaya (Anti-XSS) |
| `same_site` | `lax` | Melindungi dari serangan Cross-Site Request Forgery (CSRF) |
| `secure` | `true` (HTTPS) | Cookie hanya ditransmisikan melalui koneksi SSL/TLS terenkripsi |
| `regenerate_id` | Diaktifkan saat Login | Mencegah eksploitasi *Session Fixation* |

---

## 3. Matriks Otorisasi Berbasis Peran (Spatie RBAC Matrix)

```
Hak Akses (Permissions)             Super Admin   Sales PIC   Finance   Logistik   Mitra Agen
─────────────────────────────────────────────────────────────────────────────────────────────
Kelola Inquiries / CRM                  [v]          [v]        [ ]       [ ]         [ ]
Akses HPP & Gross Margin                [v]          [v]        [ ]       [ ]         [ ]
Approval Penawaran Margin Rendah        [v]          [ ]        [ ]       [ ]         [ ]
Penerbitan Sales Order (SO)             [v]          [v]        [v]       [ ]         [ ]
Penerbitan Delivery Order (DO)          [v]          [ ]        [ ]       [v]         [ ]
Pencairan Komisi Mitra                  [v]          [ ]        [v]       [ ]         [ ]
Portal Mandiri Komisi Pribadi           [v]          [ ]        [ ]       [ ]         [v]
Manajemen Pengguna & Konfigurasi        [v]          [ ]        [ ]       [ ]         [ ]
```

- **Isolasi Data Agen Freelance:** Agen freelance dibatasi pada portal `app.bimabergawa.com/mitra` dan dilindungi oleh *Global Scope* sehingga tidak memiliki akses membaca data prospek, penawaran, atau omzet milik agen lain.
