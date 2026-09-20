<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Identitas & Umum (group: general)
            ['key' => 'general.company_name', 'value' => 'PT. Panca Bima Bergawa', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.company_tagline', 'value' => 'One-Stop B2B Industrial General Supplier & Mitra Pengadaan Pabrik', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.company_address', 'value' => 'Gd. Wirausaha lt.1 Unit 104, Jl. H.R. Rasuna Said Kav. C5, Setiabudi, Jakarta Selatan 12920', 'group' => 'general', 'type' => 'text', 'is_public' => true],
            ['key' => 'general.contact_email', 'value' => 'sales@bimabergawa.com', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.contact_phone', 'value' => '+62 21 5292 0000', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.contact_whatsapp', 'value' => '6281112345678', 'group' => 'general', 'type' => 'string', 'is_public' => true],
            ['key' => 'general.default_timezone', 'value' => 'Asia/Jakarta', 'group' => 'general', 'type' => 'string', 'is_public' => false],
            ['key' => 'general.date_format', 'value' => 'd/m/Y', 'group' => 'general', 'type' => 'string', 'is_public' => false],

            // Komersial & Pajak Dinamis UU HPP (group: commercial_tax)
            ['key' => 'commercial_tax.tax_vat_rate', 'value' => '11.00', 'group' => 'commercial_tax', 'type' => 'decimal', 'is_public' => false],
            ['key' => 'commercial_tax.critical_margin_threshold', 'value' => '8.00', 'group' => 'commercial_tax', 'type' => 'decimal', 'is_public' => false],
            ['key' => 'commercial_tax.warning_margin_threshold', 'value' => '15.00', 'group' => 'commercial_tax', 'type' => 'decimal', 'is_public' => false],
            ['key' => 'commercial_tax.director_approval_limit', 'value' => '100000000.00', 'group' => 'commercial_tax', 'type' => 'decimal', 'is_public' => false],
            ['key' => 'commercial_tax.quote_validity_days', 'value' => '14', 'group' => 'commercial_tax', 'type' => 'integer', 'is_public' => false],
            ['key' => 'commercial_tax.company_npwp', 'value' => '01.890.123.4-012.000', 'group' => 'commercial_tax', 'type' => 'string', 'is_public' => true],
            ['key' => 'commercial_tax.company_bank_name', 'value' => 'Bank Central Asia (BCA)', 'group' => 'commercial_tax', 'type' => 'string', 'is_public' => true],
            ['key' => 'commercial_tax.company_bank_account_number', 'value' => '500-123-4567', 'group' => 'commercial_tax', 'type' => 'string', 'is_public' => true],
            ['key' => 'commercial_tax.company_bank_account_name', 'value' => 'PT. Panca Bima Bergawa', 'group' => 'commercial_tax', 'type' => 'string', 'is_public' => true],
            ['key' => 'commercial_tax.company_pkp_status', 'value' => 'Pengusaha Kena Pajak (PKP) Terdaftar KPP Pratama Jakarta Setiabudi', 'group' => 'commercial_tax', 'type' => 'string', 'is_public' => true],
            ['key' => 'commercial_tax.payment_terms_presets', 'value' => json_encode([
                ['term_name' => 'Cash in Advance (100% CBD)', 'percentage' => 100, 'trigger_event' => 'order_confirmation', 'due_days' => 0],
                ['term_name' => 'DP 50% saat PO, Pelunasan 50% sebelum kirim', 'percentage' => 50, 'trigger_event' => 'order_confirmation', 'due_days' => 0],
                ['term_name' => 'Pelunasan 50% sebelum pengiriman', 'percentage' => 50, 'trigger_event' => 'ready_to_ship', 'due_days' => 7],
                ['term_name' => 'Net 30 Hari (Corporate Credit)', 'percentage' => 100, 'trigger_event' => 'invoice_received', 'due_days' => 30],
                ['term_name' => 'Net 14 Hari', 'percentage' => 100, 'trigger_event' => 'delivery_completed', 'due_days' => 14],
            ]), 'group' => 'commercial_tax', 'type' => 'json', 'is_public' => false],

            // CRM & Sales SLA (group: crm_sales)
            ['key' => 'crm_sales.inquiry_stale_hours', 'value' => '24', 'group' => 'crm_sales', 'type' => 'integer', 'is_public' => false],
            ['key' => 'crm_sales.whatsapp_quotation_template', 'value' => 'Halo {pic_name}, terima kasih telah menghubungi PT. Panca Bima Bergawa. Berikut kami lampirkan dokumen penawaran resmi {quote_number}.', 'group' => 'crm_sales', 'type' => 'text', 'is_public' => false],

            // Logistik & Gudang (group: logistics)
            ['key' => 'logistics.default_warehouse_origin', 'value' => 'Gudang Pusat Rasuna Said, Jakarta Selatan', 'group' => 'logistics', 'type' => 'string', 'is_public' => false],
            ['key' => 'logistics.authorized_couriers', 'value' => json_encode(['Dakota Cargo', 'Baraka Sarana Tama', 'JNE Trucking', 'Kurir Internal PBB']), 'group' => 'logistics', 'type' => 'json', 'is_public' => false],

            // Konten CMS Beranda (group: cms_content)
            ['key' => 'cms.hero_headline', 'value' => 'Mitra Pengadaan Industri & Distributor B2B Terpadu', 'group' => 'cms_content', 'type' => 'string', 'is_public' => true],
            ['key' => 'cms.hero_subheadline', 'value' => 'Menyediakan pengadaan terpadu elektrikal, mekanikal, otomasi pabrik, safety K3, perkakas MRO, dan perlengkapan industri untuk pabrik manufaktur dan proyek komersial di seluruh Indonesia.', 'group' => 'cms_content', 'type' => 'text', 'is_public' => true],
            ['key' => 'cms.stats_counter', 'value' => json_encode([
                ['number' => '18', 'label' => 'Prinsipal Global Resmi'],
                ['number' => '10,000+', 'label' => 'SKU & Item Katalog'],
                ['number' => '500+', 'label' => 'Klien Korporat & Proyek'],
                ['number' => '100%', 'label' => 'Keaslian & Garansi Resmi'],
            ]), 'group' => 'cms_content', 'type' => 'json', 'is_public' => true],

            // Halaman Legalitas (group: legal_page)
            ['key' => 'legal.terms_and_conditions', 'value' => 'Syarat dan Ketentuan resmi transaksi komersial B2B PT. Panca Bima Bergawa.', 'group' => 'legal_page', 'type' => 'text', 'is_public' => true],
            ['key' => 'legal.privacy_policy', 'value' => 'Kebijakan Privasi PT. Panca Bima Bergawa berlandaskan kepatuhan UU Perlindungan Data Pribadi No. 27/2022.', 'group' => 'legal_page', 'type' => 'text', 'is_public' => true],
            ['key' => 'legal.warranty_policy', 'value' => 'Kebijakan Garansi Resmi Manufaktur Prinsipal Schneider Electric, Philips Lighting, ABB, Legrand, Kitz, Grundfos, 3M, Bosch, dan seluruh prinsipal mitra resmi PT. Panca Bima Bergawa.', 'group' => 'legal_page', 'type' => 'text', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
