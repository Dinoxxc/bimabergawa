# 03. Engine Import Massal Excel Katalog Produk & Batch Chunking
## Data Migration & Seeding Suite (Data-Migration-Seeding)

| Parameter Dokumen | Keterangan Formal |
| :--- | :--- |
| **Nama Proyek** | B2B Multi-Brand Product Showcase & Internal Commercial ERP |
| **Entitas Perusahaan** | **PT. Panca Bima Bergawa** (Disingkat: **PBB**) |
| **Alamat Kantor Pusat** | Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920 |
| **Nomor Dokumen** | `DOC-PBB-MIG-03` |
| **Versi Dokumen** | 1.3.0 |
| **Tanggal Efektif** | September 2026 |
| **Klasifikasi Akses** | Internal Confidential & Engineering Reference |

### Riwayat Revisi Dokumen (Document Version Control)

| Versi | Tanggal | Penulis / Peran | Status | Ringkasan Perubahan |
| :---: | :---: | :--- | :---: | :--- |
| `1.0.0` | 01 Sep 2026 | Backend Lead | Draf Awal | Format template Excel import produk |
| `1.1.0` | 03 Sep 2026 | Performance Engineer | Ditinjau | Implementasi batch chunking 100 baris & proteksi RAM |
| `1.2.0` | 05 Sep 2026 | Principal System Architect | Disetujui | Penyelarasan validasi HPP internal & mapping JSON spek |
| `1.3.0` | 05 Sep 2026 | Principal Integration Engineer | Disetujui | Pemisahan berkas mandiri engine import Excel & zero emoji |

---

## 1. Spesifikasi Template Berkas Excel (`.xlsx` / `.csv`)

Untuk mempermudah input massal ribuan nomor model (*Part Number*) dari daftar harga prinsipal, sistem menyediakan template standar dengan susunan kolom berikut:

| No | Header Kolom Excel | Keterangan Atribut | Aturan Validasi | Contoh Isian |
| :---: | :--- | :--- | :--- | :--- |
| 1 | `brand_name` | Nama Brand Prinsipal | `required\|string\|max:100` | `Ebara` |
| 2 | `category_name` | Nama Sub-Kategori | `required\|string\|max:100` | `End Suction Centrifugal Pump` |
| 3 | `model_number` | Nomor Model / SKU Resmi | `required\|string\|max:100\|unique:products,model_number` | `FSHA 65-50-200` |
| 4 | `product_name` | Nama Judul Produk | `required\|string\|max:255` | `Centrifugal End Suction Pump 65x50` |
| 5 | `hpp_cost_price` | HPP Beli Internal (IDR) | `required\|numeric\|min:0` | `18000000` |
| 6 | `stock_status` | Status Stok Fisik | `required\|in:ready,indent` | `ready` |
| 7 | `short_description`| Ringkasan Kegunaan | `nullable\|string\|max:500` | `Pompa sentrifugal sirkulasi air pendingin.` |
| 8 | `spec_flow_rate` | Kapasitas Aliran | `nullable\|string\|max:100` | `50 m3/h` |
| 9 | `spec_head` | Total Head Dinamis | `nullable\|string\|max:100` | `45 Meter` |
| 10 | `spec_power` | Daya Motor Penggerak | `nullable\|string\|max:100` | `11 kW (15 HP)` |
| 11 | `spec_material` | Material Casing | `nullable\|string\|max:100` | `Cast Iron FC200` |
| 12 | `is_featured` | Tampilkan di Beranda | `nullable\|in:0,1` | `1` |

---

## 2. Implementasi Engine Import Laravel (`CatalogImport.php`)

```php
namespace App\Imports;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithValidation;

class CatalogImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts, WithValidation
{
    public function model(array $row)
    {
        // 1. Resolve atau Buat Brand Master
        $brand = Brand::firstOrCreate(
            ['name' => trim($row['brand_name'])],
            ['slug' => Str::slug(trim($row['brand_name'])), 'is_active' => true]
        );

        // 2. Resolve atau Buat Sub-Kategori
        $category = Category::firstOrCreate(
            ['name' => trim($row['category_name'])],
            ['slug' => Str::slug(trim($row['category_name'])), 'is_active' => true]
        );

        // 3. Susun Spesifikasi Teknis Dinamis JSON
        $specs = [
            'flow_rate'       => $row['spec_flow_rate'] ?? null,
            'head'            => $row['spec_head'] ?? null,
            'motor_power'     => $row['spec_power'] ?? null,
            'casing_material' => $row['spec_material'] ?? null,
        ];

        return new Product([
            'brand_id'          => $brand->id,
            'category_id'       => $category->id,
            'model_number'      => trim($row['model_number']),
            'name'              => trim($row['product_name']),
            'slug'              => Str::slug(trim($row['brand_name']) . '-' . trim($row['model_number'])),
            'hpp_cost_price'    => (float) $row['hpp_cost_price'],
            'stock_status'      => strtolower(trim($row['stock_status'])) === 'indent' ? 'indent' : 'ready',
            'technical_specs'   => array_filter($specs),
            'short_description' => $row['short_description'] ?? null,
            'is_featured'       => (bool) ($row['is_featured'] ?? false),
            'is_active'         => true,
        ]);
    }

    public function rules(): array
    {
        return [
            'brand_name'     => 'required|string|max:100',
            'category_name'  => 'required|string|max:100',
            'model_number'   => 'required|string|max:100',
            'product_name'   => 'required|string|max:255',
            'hpp_cost_price' => 'required|numeric|min:0',
            'stock_status'   => 'required|in:ready,indent,READY,INDENT',
        ];
    }

    public function chunkSize(): int
    {
        return 100; // Membatasi konsumsi memori per 100 baris
    }

    public function batchSize(): int
    {
        return 100; // Eksekusi batch INSERT 100 record per kueri MySQL
    }
}
```

---

## 3. Penanganan Kesalahan & Validasi Data Gagal

Jika terdapat baris data yang tidak memenuhi aturan validasi:
1. **Error Summary Log:** Baris yang gagal dicatat pada tabel `import_failures` lengkap dengan nomor baris Excel dan alasan penolakan.
2. **Transactional Safety:** Baris yang valid tetap diproses, sementara baris yang gagal diabaikan dan diekspor ke file Excel koreksi (*Rejection Sheet*).
