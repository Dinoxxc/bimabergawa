# 01. Pipeline GitHub Actions & Alur CI/CD
## CI/CD & Deployment Suite (CICD-Deployment)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-DEP-01` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | DevOps Engineer | Draf Awal | Definisi alur workflow GitHub Actions & SSH trigger |
| `1.1.0` | 03 Sep 2026 | Security Specialist | Ditinjau | Penambahan enkripsi secrets & konfigurasi PHP 8.3 extensions |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan build Vite & Pest automated testing runner |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri workflow YAML, branching model, & zero emoji |

---

## 1. Strategi Percabangan Git (Branching Strategy)

- **Branch `main`:** Cabang produksi resmi. Setiap *merge* atau *push* ke `main` otomatis memicu pipeline deployment ke server produksi Hostinger.
- **Branch `staging` / `develop`:** Cabang pra-rilis untuk pengujian fitur baru sebelum digabungkan ke `main`.
- **Branch `feature/*`:** Cabang kerja pengembang lokal yang wajib melalui *Pull Request* (PR) dan lolos seluruh *Automated Tests*.

---

## 2. Definisi Lengkap Workflow CI/CD (`.github/workflows/deploy.yml`)

```yaml
name: Production CI/CD Pipeline - PT. Panca Bima Bergawa

on:
  push:
    branches:
      - main

jobs:
  lint-test-and-build:
    name: Code Quality, Testing & Build Assets
    runs-on: ubuntu-latest

    steps:
      - name: 1. Checkout Repositori
        uses: actions/checkout@v4

      - name: 2. Setup Lingkungan PHP 8.3
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, xml, ctype, iconv, intl, pdo_mysql, bcmath, gd, zip
          coverage: none

      - name: 3. Cache Composer Dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
          restore-keys: |
            ${{ runner.os }}-php-

      - name: 4. Instalasi Dependensi PHP
        run: composer install --prefer-dist --no-progress --no-interaction

      - name: 5. Eksekusi Pengujian Otomatis (Pest / PHPUnit)
        run: ./vendor/bin/pest --ci

      - name: 6. Setup Node.js v20
        uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: 'npm'

      - name: 7. Instalasi Dependensi NPM & Kompilasi Aset Vite
        run: |
          npm ci
          npm run build

      - name: 8. Eksekusi Deployment SSH ke Hostinger Server
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.HOSTINGER_SSH_HOST }}
          username: ${{ secrets.HOSTINGER_SSH_USER }}
          key: ${{ secrets.HOSTINGER_SSH_PRIVATE_KEY }}
          port: ${{ secrets.HOSTINGER_SSH_PORT }}
          script: |
            cd /home/${{ secrets.HOSTINGER_SSH_USER }}/domains/bimabergawa.com/public_html
            bash deploy.sh
```

---

## 3. Konfigurasi Rahasia Lingkungan (GitHub Actions Secrets)

Seluruh kredensial sensitif disimpan pada menu **Settings -> Secrets and variables -> Actions** repositori GitHub:

| Nama Secret GitHub | Tipe Nilai | Deskripsi & Nilai Rujukan |
| :--- | :--- | :--- |
| `HOSTINGER_SSH_HOST` | IP / Hostname | IP server SSH Hostinger (e.g. `156.67.218.XX`) |
| `HOSTINGER_SSH_USER` | Username | Akun username cPanel/hPanel (e.g. `u123456789`) |
| `HOSTINGER_SSH_PORT` | Port Angka | Port SSH resmi Hostinger (`65002`) |
| `HOSTINGER_SSH_PRIVATE_KEY`| Private Key | Kunci privat OpenSSH (`id_rsa` atau `id_ed25519`) |

---

## 4. Prosedur Pembuatan & Pemasangan SSH Key Pair

1. **Generate SSH Key di Komputer Pengembang:**
   ```bash
   ssh-keygen -t ed25519 -C "deploy@bimabergawa.com" -f ./deploy_key
   ```
2. **Pasang Public Key di hPanel Hostinger:**
   - Buka menu **Advanced -> SSH Access -> Authorized Keys**.
   - Tempel isi berkas `deploy_key.pub`.
3. **Simpan Private Key di GitHub:**
   - Salin isi berkas `deploy_key` dan masukkan ke secret `HOSTINGER_SSH_PRIVATE_KEY`.
