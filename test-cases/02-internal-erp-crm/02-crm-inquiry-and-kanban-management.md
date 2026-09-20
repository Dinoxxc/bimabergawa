# Test Cases: CRM Inquiries, Kanban Board, & SLA Stale Management
## Domain 02: Internal Commercial ERP (app.bimabergawa.com)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-TC-ERP-02` |
| **Versi Dokumen** | 1.4.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & QA/Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | CRM QA Tester | Draf Awal | Pengujian manajemen inkuiri tabular dasar |
| `1.1.0` | 03 Sep 2026 | Test Automation Engineer | Ditinjau | Penambahan uji Kanban drag-and-drop & SLA aging stale badge |
| `1.2.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Penyelarasan audit log Spatie & penugasan PIC sales |
| `1.3.0` | 05 Sep 2026 | Principal QA Architect | Disetujui | Ekspansi 16 skenario uji mendalam & standardisasi status Not Executed |
| `1.4.0` | 06 Sep 2026 | Principal QA Architect | Disetujui | Penambahan uji notifikasi lonceng in-app drawer, linimasa diskusi internal, autocomplete mention `@User`, dan visibilitas ganda |

---

## 1. Matriks Ketertelusuran Rujukan Teknis (Technical Traceability)

- **Dokumen Acuan Spesifikasi:**
  - `DOC-PBB-PRD-03` (Spesifikasi CRM Inquiries, Kanban Board, & State Machine)
  - `DOC-PBB-SRS-04` (Kebutuhan Fungsional FR-ERP-04 s.d. FR-ERP-07)
  - `DOC-PBB-SDD-01` (Skema Basis Data Tabel `notifications` & `internal_comments`)
  - `DOC-PBB-SDD-04` (Filament CRM Resources, Livewire Internal Comments Timeline, & Mention Popover)
  - `DOC-PBB-UIUX-04` (UX Kanban Board, Stale SLA Badges, Notification Drawer, & Contextual Discussion UX)
- **Modul Sistem:** Modul Manajemen Prospek, CRM Inquiries, & Koordinasi Internal
- **Target Kelas / Controller / Service:**
  - `App\Filament\Resources\InquiryResource`
  - `App\Filament\Resources\InquiryResource\Pages\ListInquiriesKanban`
  - `App\Livewire\InternalCommentsTimeline`
  - `App\Services\InquiryAssignmentService`
  - `App\Models\Inquiry`, `App\Models\InternalComment`, `App\Models\Notification`
- **Target Method:** `changeStatus()`, `assignSalesPic()`, `calculateSlaAging()`, `recordActivity()`, `postComment()`, `dispatchMentionNotifications()`

---

## 2. Matriks Kasus Uji Komprehensif (Test Scenarios Matrix)

| ID Kasus Uji | Kategori | Skenario Pengujian | Prasyarat (Pre-conditions) | Data Input / Aksi | Hasil yang Diharapkan (Expected Result) | Status |
| :--- | :---: | :--- | :--- | :--- | :--- | :---: |
| **`TC-ERP-CRM-001`** | Positif | Tampilan Daftar Inkuiri Tabular & Filter Multi-Parameter | Inkuiri publik tersedia di database | Buka menu CRM -> Inquiries, terapkan filter status & PIC | Tabel memuat inkuiri dengan kolom: Nomor Inkuiri, Nama Perusahaan, PIC Sales, Status Badge, dan Tanggal Masuk. Filter berfungsi akurat. | Not Executed |
| **`TC-ERP-CRM-002`** | Positif | Pergantian Tampilan Antara Tabel dan Kanban Board | Inkuiri aktif di DB | Klik toggle button "Tampilan Kanban" | Antarmuka beralih ke papan Kanban dengan kolom: *New Lead, Contacted, Quoted, Negotiation, Won, Lost*. | Not Executed |
| **`TC-ERP-CRM-003`** | Positif | Transisi Status Inkuiri via Drag-and-Drop Kanban | Inkuiri pada kolom *New Lead* | Geser kartu inkuiri ke kolom *Contacted* | Status inkuiri di database terupdate menjadi `contacted`, event dicatat di `activity_log`, posisi kartu menetap di kolom baru. | Not Executed |
| **`TC-ERP-CRM-004`** | Positif | Penegakan Indikator Peringatan SLA Stale Aging (> 24 Jam) | Inkuiri berstatus `lead` berusia 25 jam | Buka Kanban board | Kartu inkuiri menampilkan badge peringatan kuning/oranye: "SLA Warning: 25 Jam Belum Direspons". | Not Executed |
| **`TC-ERP-CRM-005`** | Positif | Penegakan Indikator Peringatan SLA Kritis (> 48 Jam) | Inkuiri berstatus `lead` berusia 49 jam | Buka Kanban board | Kartu inkuiri menampilkan badge merah menyala: "SLA Danger: > 48 Jam Belum Diproses", kartu diposisikan di prioritas teratas kolom. | Not Executed |
| **`TC-ERP-CRM-006`** | Positif | Penugasan PIC Sales (*Sales Assignment Workflow*) | Inkuiri baru belum memiliki PIC | Buka inkuiri -> Klik aksi "Tugaskan PIC" -> Pilih Sales | Field `assigned_to` terisi ID Sales terpilih, notifikasi internal terkirim ke panel sales yang ditugaskan. | Not Executed |
| **`TC-ERP-CRM-007`** | Positif | Pratinjau & Unduh Lampiran BOQ PDF dari Detail Inkuiri | Inkuiri memiliki berkas `boq_attachment` | Klik tombol "Lihat Lampiran BOQ" pada panel detail | Berkas PDF terbuka di modal penampil dokumen atau diunduh dengan aman dari disk privat via temporary signed URL. | Not Executed |
| **`TC-ERP-CRM-008`** | Positif | Konversi Inkuiri Langsung Menjadi Draf Quotation | Inkuiri berstatus `contacted` | Klik tombol aksi "Buat Quotation Resmi" | Sistem membuka form Quotation Builder dengan data perusahaan, PIC, dan daftar item SKU inkuiri otomatis terisi (*auto-populated*). | Not Executed |
| **`TC-ERP-CRM-009`** | Positif | Penolakan / Status Lost dengan Alasan Wajib (*Rejection Reason*) | Inkuiri dalam proses negosiasi | Geser ke kolom *Lost* -> Muncul modal form | Form mewajibkan pengisian `lost_reason` (cth: "Harga kompetitor lebih murah"). Setelah disubmit, inkuiri terarsip berstatus `lost`. | Not Executed |
| **`TC-ERP-CRM-010`** | Negatif | Validasi Penolakan Transisi Status Lost Tanpa Alasan | Inkuiri digeser ke status *Lost* | Form alasan dikosongkan dan klik submit | Form memunculkan error validasi: "Alasan pembatalan/kegagalan inkuiri wajib diisi" dan status inkuiri tidak berubah. | Not Executed |
| **`TC-ERP-CRM-011`** | Negatif | Akses atau Modifikasi Inkuiri oleh Sales Non-PIC (Isolasi Sales) | Inkuiri ditugaskan ke Sales A | Sales B mencoba mengedit/mengubah status inkuiri | Sistem memblokir aksi dengan error 403 Forbidden atau menyembunyikan tombol edit sesuai kebijakan Spatie RBAC. | Not Executed |
| **`TC-ERP-CRM-012`** | Negatif | Penugasan PIC ke Pengguna Non-Aktif / Akun Dihapus | Admin membuka modal penugasan | Coba pilih user dengan status `is_active = false` | User non-aktif difilter dari daftar pilihan dropdown sehingga tidak dapat dipilih. | Not Executed |
| **`TC-ERP-CRM-013`** | Keamanan | Pencegahan Bypass Hak Akses Modifikasi Inkuiri via API | Inkuiri milik Sales A | Sales B mengirim request PATCH langsung via Postman | Controller memverifikasi otorisasi via `InquiryPolicy::update()`, mengembalikan status HTTP 403 Forbidden. | Not Executed |
| **`TC-ERP-CRM-014`** | Keamanan | Pencatatan Jejak Audit Mutasi Status (*Spatie ActivityLog*) | Status inkuiri diubah dari `contacted` ke `quoted` | Periksa tabel `activity_log` | Tercatat 1 baris log memuat: `causer_id` (User ID), `subject_id` (Inquiry ID), nilai lama `contacted`, dan nilai baru `quoted`. | Not Executed |
| **`TC-ERP-CRM-015`** | Batas Nilai | Penanganan Inkuiri dengan Jumlah Item SKU Sangat Besar (> 100 SKU) | Inkuiri hasil bulk paste dengan 100 baris item | Buka halaman detail inkuiri | Tabel rincian item termuat dengan paginasi lancar tanpa memicu memory limit PHP (512 MB). | Not Executed |
| **`TC-ERP-CRM-016`** | Konkurensi | Penanganan Pembaruan Status Serentak oleh 2 Pengguna | Sales dan Admin membuka inkuiri sama | Kedua pengguna mengubah status secara bersamaan | Sistem menggunakan *optimistic locking* (`updated_at` check) sehingga pembaruan kedua meminta konfirmasi data terbaru. | Not Executed |
| **`TC-ERP-CRM-017`** | Notifikasi | Lonceng Notifikasi In-App ERP Drawer & Unread Counter | User memiliki notifikasi baru di tabel `notifications` | Buka panel ERP, periksa topbar icon lonceng | Icon lonceng menampilkan badge merah `[Notif: N]`. Klik lonceng membuka slide-over drawer memuat daftar notifikasi. Klik notifikasi menandai `read_at = NOW()` dan mengarahkan ke dokumen. | Not Executed |
| **`TC-ERP-CRM-018`** | Kolaborasi | Penambahan Catatan Koordinasi Berkonteks via Linimasa Livewire | Halaman View Inquiry dibuka oleh Staf | Isi teks catatan pada form linimasa dan lampirkan file PDF | Record tersimpan di tabel `internal_comments` polimorfik (`commentable_type = Inquiry`, `commentable_id = ?`), komponen Livewire refresh menampilkan avatar, role, dan timestamp relatif. | Not Executed |
| **`TC-ERP-CRM-019`** | Kolaborasi | Autocomplete Mention Pengguna (`@User`) & Pemicu Notifikasi | Staf mengetik `@` pada textarea linimasa catatan | Ketik `@bambang` -> pilih user -> kirim catatan | Sistem menyimpan array `mentioned_user_ids` (JSON) pada `internal_comments`, membuat notifikasi di tabel `notifications` untuk user bersangkutan, dan mengirimkan surel formal secara instan. | Not Executed |
| **`TC-ERP-CRM-020`** | Visibilitas | Penegakan Visibilitas Ganda: Catatan Staf vs Pesan Agen | Staf membuka inkuiri yang diajukan agen freelance | Pilih tipe: `internal` untuk rahasia HPP, atau `agent_visible` untuk agen | Catatan `internal` hanya terlihat di panel internal PBB; catatan `agent_visible` muncul di portal agen freelance dan panel internal secara sinkron. | Not Executed |

---

## 3. Prosedur Eksekusi Kasus Uji Mendalam

### 3.1 Prosedur Uji TC-ERP-CRM-003 (Drag-and-Drop Transisi Status Kanban)

1. **Langkah 1:** Login ke `https://app.bimabergawa.com` sebagai pengguna peran `Sales PIC`.
2. **Langkah 2:** Navigasikan ke menu **CRM -> Inquiries** -> Pilih tab **Kanban**.
3. **Langkah 3:** Pilih satu kartu inkuiri bernomor `PBB/INQ/2026/092F9C/0001` pada kolom *New Lead*.
4. **Langkah 4:** Drag kartu tersebut dan letakkan pada kolom *Contacted*.
5. **Verifikasi Teknis:**
   - Permintaan asynchronous Livewire/Filament terkirim ke server.
   - Status inkuiri di tabel database `inquiries` berubah dari `lead` menjadi `contacted`.
   - Kolom `activity_log` mencatat: "Inquiry PBB/INQ/2026/092F9C/0001 status changed from lead to contacted by User #2".

### 3.2 Prosedur Uji TC-ERP-CRM-004 (Verifikasi Peringatan SLA Stale)

1. **Langkah 1:** Jalankan query database untuk mensimulasikan inkuiri lama:
   ```sql
   UPDATE inquiries SET created_at = NOW() - INTERVAL 26 HOUR WHERE id = 1 AND status = 'lead';
   ```
2. **Langkah 2:** Refresh halaman papan Kanban CRM.
3. **Verifikasi Teknis:**
   - Kartu inkuiri memunculkan badge peringatan oranye berlabel "SLA Warning: 26 Jam".
   - Helper function `calculateSlaAging()` menghitung selisih waktu secara tepat terhadap `now()`.
