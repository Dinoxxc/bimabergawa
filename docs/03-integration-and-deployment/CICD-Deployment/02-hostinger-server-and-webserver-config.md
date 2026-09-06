# 02. Konfigurasi Server Hostinger, PHP 8.3, & Web Server LiteSpeed
## CI/CD & Deployment Suite (CICD-Deployment)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-DEP-02` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Infrastructure Lead | Draf Awal | Konfigurasi dasar PHP 8.3 & LiteSpeed `.htaccess` |
| `1.1.0` | 03 Sep 2026 | DevOps Specialist | Ditinjau | Penambahan aturan caching LSCache & DocumentRoot subdomain |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Konfigurasi OPcache performa tinggi & perizinan folder storage |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri server config, security headers, & zero emoji |

---

## 1. Spesifikasi Runtime PHP 8.3 di hPanel Hostinger

Pada menu **Advanced -> PHP Configuration**, terapkan nilai parameter produksi berikut:

| Direktif PHP (`php.ini`) | Nilai Konfigurasi Produksi | Rasional Kebutuhan Sistem |
| :--- | :--- | :--- |
| `php_version` | `8.3` | Kompatibilitas Laravel 11.x & performa JIT compiler |
| `memory_limit` | `512M` | Menangani import katalog Excel & render DomPDF |
| `max_execution_time`| `120` | Menjamin proses batch SKU tidak timeout |
| `upload_max_filesize`| `32M` | Upload lampiran BOQ & berkas pendaftaran mitra |
| `post_max_size` | `64M` | Menampung multi-part form data dan batch image |
| `opcache.enable` | `1` | Cache bytecode PHP di memori untuk latensi kilat |
| `opcache.memory_consumption`| `128` | Alokasi RAM cache OPcache |

---

## 2. Pemetaan Multi-Domain DocumentRoot di hPanel

Kedua domain diarahkan ke direktori `public` dari instalasi Laravel:

```
/home/uXXXX/domains/bimabergawa.com/
├── public_html/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/                 <--- Target DocumentRoot Web Server
│   │   ├── index.php
│   │   ├── build/              (Aset CSS & JS dari Vite)
│   │   ├── storage/            (Symlink ke storage/app/public)
│   │   └── .htaccess
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
```

- **Konfigurasi Subdomain ERP di hPanel:**
  - Buka menu **Domains -> Subdomains**.
  - Buat subdomain: `app`.
  - Atur **Custom Folder for Subdomain** ke: `public_html/public`.

---

## 3. Konfigurasi Lengkap `.htaccess` untuk LiteSpeed Web Server

Berkas `.htaccess` yang diletakkan di dalam folder `public_html/public/.htaccess`:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # 1. Paksa HTTPS untuk seluruh koneksi
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # 2. Teruskan Authorization Header ke PHP
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # 3. Hilangkan Trailing Slashes jika bukan direktori nyata
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # 4. Tangani Front Controller Laravel
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# 5. Header Keamanan & Proteksi
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# 6. Browser Caching untuk Aset Statis
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

---

## 4. Standar Perizinan Hak Akses Berkas (File & Directory Permissions)

```bash
# Perizinan direktori standar
find /home/uXXXX/domains/bimabergawa.com/public_html -type d -exec chmod 755 {} \;

# Perizinan berkas standar
find /home/uXXXX/domains/bimabergawa.com/public_html -type f -exec chmod 644 {} \;

# Hak tulis khusus untuk direktori penyimpanan Laravel
chmod -R 775 /home/uXXXX/domains/bimabergawa.com/public_html/storage
chmod -R 775 /home/uXXXX/domains/bimabergawa.com/public_html/bootstrap/cache
```
