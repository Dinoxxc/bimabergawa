# 03. Skrip Deployment Produksi, Zero-Downtime, & Prosedur Rollback
## CI/CD & Deployment Suite (CICD-Deployment)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-DEP-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | DevOps Engineer | Draf Awal | Skrip deployment dasar `deploy.sh` |
| `1.1.0` | 03 Sep 2026 | Release Manager | Ditinjau | Penambahan maintenance bypass secret & caching Filament |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Prosedur mitigasi kegagalan migrasi & rollback darurat |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri skrip deploy, langkah rollback, & zero emoji |

---

## 1. Skrip Eksekusi Deployment di Server (`deploy.sh`)

Berkas skrip diletakkan pada root proyek server (`/home/uXXXX/domains/bimabergawa.com/public_html/deploy.sh`) dan diberikan hak eksekusi `chmod +x deploy.sh`:

```bash
#!/bin/bash
set -e

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
echo "[DEPLOY-PBB] [$TIMESTAMP] Memulai prosedur deployment produksi..."

# 1. Masuk ke direktori kerja aplikasi
cd /home/uXXXX/domains/bimabergawa.com/public_html

# 2. Aktifkan Maintenance Mode dengan Secret Bypass Token (untuk rilis migrasi besar)
# php artisan down --render="errors::503" --secret="pbb-release-bypass-2026"

# 3. Buat Snapshot Commit Terakhir untuk Keperluan Rollback
PREV_COMMIT=$(git rev-parse HEAD)
echo "[DEPLOY-PBB] Commit saat ini: $PREV_COMMIT"

# 4. Tarik Kode Sumber Terbaru dari Repositori GitHub
echo "[DEPLOY-PBB] Menjalankan git pull origin main..."
git pull origin main

# 5. Instalasi dan Optimasi Dependensi PHP Produksi
echo "[DEPLOY-PBB] Mengoptimasi dependensi composer..."
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

# 6. Pastikan Symlink Direktori Storage Publik Terpasang
echo "[DEPLOY-PBB] Memeriksa storage symlink..."
php artisan storage:link || true

# 7. Eksekusi Migrasi Database Terproteksi
echo "[DEPLOY-PBB] Menjalankan migrasi database..."
php artisan migrate --force

# 8. Bersihkan dan Bangun Ulang Seluruh Cache Performa
echo "[DEPLOY-PBB] Memanaskan cache aplikasi..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
php artisan icons:cache

# 9. Nonaktifkan Maintenance Mode
# php artisan up

echo "[DEPLOY-PBB] Deployment berhasil diselesaikan secara sempurna pada $(date +"%Y-%m-%d %H:%M:%S")."
```

---

## 2. Pemanasan Cache Aplikasi (Cache Warming Checklist)

| Perintah Artisan | Tujuan Optimasi |
| :--- | :--- |
| `php artisan config:cache` | Menggabungkan seluruh file config menjadi 1 berkas PHP cepat |
| `php artisan route:cache` | Mengompilasi pohon routing untuk pencocokan URL berkecepatan tinggi |
| `php artisan view:cache` | Melakukan pre-kompilasi seluruh template Blade ke PHP murni |
| `php artisan filament:cache-components` | Mengompilasi seluruh skema Form, Table, dan Infolist Filament |
| `php artisan icons:cache` | Melakukan caching pustaka Heroicons & Lucide Icons |

---

## 3. Prosedur Kontinjensi & Rollback Darurat (Rollback Procedures)

Jika terjadi kendala kritis setelah deployment (misal: fatal error pada database atau aplikasi), tim teknis segera menjalankan langkah pemulihan berikut:

### Langkah 1: Kembalikan Kode Sumber ke Commit Sebelumnya
```bash
cd /home/uXXXX/domains/bimabergawa.com/public_html
git reset --hard $PREV_COMMIT
```

### Langkah 2: Rollback Migrasi Database Terakhir (Jika Diperlukan)
```bash
php artisan migrate:rollback --step=1 --force
```

### Langkah 3: Bersihkan dan Bangun Ulang Cache
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
```

### Langkah 4: Verifikasi Ketersediaan Layanan
Lakukan pengujian respon HTTP pada kedua domain:
```bash
curl -I https://bimabergawa.com
curl -I https://app.bimabergawa.com/login
```
