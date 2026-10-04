<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class InquirySampleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@bimabergawa.com')->first();
        $adminId = $admin?->id;

        // 1. Column NEW: Fresh Lead (< 24h, 3h old)
        $inq1 = Inquiry::firstOrCreate(
            ['inquiry_number' => 'PBB/INQ/2026/10/0001'],
            [
                'company_name' => 'PT. Wijaya Karya (WIKA) MEP Division',
                'customer_name' => 'Ir. Hendra Kusuma',
                'phone_wa' => '081288991122',
                'email' => 'hendra.kusuma@wika-mep.co.id',
                'project_location' => 'Gedung Tower Sentra Komersial Kuningan, Jakarta Selatan',
                'type' => 'bulk_cart',
                'source' => 'website_rfq',
                'status' => 'new',
                'notes' => 'Urgent procurement for Panel Distribusi Utama (LVMDP) Substation 2.',
            ]
        );
        if ($inq1->wasRecentlyCreated) {
            $inq1->timestamps = false;
            $inq1->created_at = now()->subHours(3);
            $inq1->save();

            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq1->id, 'model_number' => 'LV429630'],
                [
                    'item_name' => 'Schneider Compact NSX100F 3P 36kA 100A TMD',
                    'quantity' => 12,
                    'uom' => 'UNIT',
                    'custom_notes' => 'Official Schneider Electric authentic certificate required.',
                ]
            );
            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq1->id, 'model_number' => 'A9F74320'],
                [
                    'item_name' => 'Schneider Acti9 iC60N 3P 20A Miniature Circuit Breaker',
                    'quantity' => 45,
                    'uom' => 'PCS',
                ]
            );
        }

        // 2. Column NEW: Warning SLA Lead (> 24h, 28h old)
        $inq2 = Inquiry::firstOrCreate(
            ['inquiry_number' => 'PBB/INQ/2026/10/0002'],
            [
                'company_name' => 'PT. Total Bangun Persada MEP',
                'customer_name' => 'Ibu Ratna Dewi, S.T.',
                'phone_wa' => '081377441100',
                'email' => 'ratna.dewi@totalbp.com',
                'project_location' => 'Data Center Campus Cibitung Tahap 2, Bekasi',
                'type' => 'manual_offline',
                'source' => 'direct_whatsapp',
                'status' => 'new',
                'notes' => 'Need official pump catalog & test report from Grundfos before quotation.',
            ]
        );
        if ($inq2->wasRecentlyCreated) {
            $inq2->timestamps = false;
            $inq2->created_at = now()->subHours(28);
            $inq2->save();

            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq2->id, 'model_number' => 'CR15-3 A-FGJ-A-E-HQQE'],
                [
                    'item_name' => 'Grundfos Vertical Multistage Centrifugal Pump CR 15-3',
                    'quantity' => 4,
                    'uom' => 'SET',
                    'custom_notes' => 'Motor 3.0 kW 380V 50Hz, complete with counter flanges & bolts.',
                ]
            );
        }

        // 3. Column NEW: Danger SLA Lead (> 48h, 54h old)
        $inq3 = Inquiry::firstOrCreate(
            ['inquiry_number' => 'PBB/INQ/2026/10/0003'],
            [
                'company_name' => 'PT. Cikarang Presisi Manufaktur',
                'customer_name' => 'Bpk. Bambang Sutrisno',
                'phone_wa' => '081199882233',
                'email' => 'bambang.s@cikarang-precision.co.id',
                'project_location' => 'Kawasan Industri GIIC Cikarang Pusat',
                'type' => 'single_direct',
                'source' => 'website_rfq',
                'status' => 'new',
                'notes' => 'Automated Stamping Line Retrofit 2026. Requires urgent response from automation sales specialist.',
            ]
        );
        if ($inq3->wasRecentlyCreated) {
            $inq3->timestamps = false;
            $inq3->created_at = now()->subHours(54);
            $inq3->save();

            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq3->id, 'model_number' => 'CP1E-N40DT-D'],
                [
                    'item_name' => 'Omron Programmable Logic Controller CP1E-N40DT-D',
                    'quantity' => 6,
                    'uom' => 'UNIT',
                    'custom_notes' => '24 VDC Power Supply, 24 Inputs, 16 Transistor Outputs.',
                ]
            );
            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq3->id, 'model_number' => 'MY4N-GS 24VDC'],
                [
                    'item_name' => 'Omron Industrial General Purpose Relay MY4N-GS 24VDC',
                    'quantity' => 100,
                    'uom' => 'PCS',
                ]
            );
        }

        // 4. Column CONTACTED: Lead being communicated
        $inq4 = Inquiry::firstOrCreate(
            ['inquiry_number' => 'PBB/INQ/2026/10/0004'],
            [
                'assigned_user_id' => $adminId,
                'company_name' => 'PT. Hutama Mandiri Elektrikal Panel',
                'customer_name' => 'Agus Priyono',
                'phone_wa' => '081566778899',
                'email' => 'agus@hutamamandiri.co.id',
                'project_location' => 'Kawasan Industri MM2100 Cikarang Barat',
                'type' => 'manual_offline',
                'source' => 'walk_in_phone',
                'status' => 'contacted',
                'first_contacted_at' => now()->subHours(12),
                'notes' => 'Panel Synchronizing PLN-Genset 2x1500kVA. Follow up WA call done. Client requested Schneider Masterpact ACB specifications.',
            ]
        );
        if ($inq4->wasRecentlyCreated) {
            $inq4->timestamps = false;
            $inq4->created_at = now()->subHours(16);
            $inq4->save();

            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq4->id, 'model_number' => 'MTZ216H13P'],
                [
                    'item_name' => 'Schneider Masterpact MTZ2 16 H1 3P 1600A Drawout ACB',
                    'quantity' => 2,
                    'uom' => 'UNIT',
                    'custom_notes' => 'Equipped with Micrologic 5.0X trip unit and motorized mechanism 220VAC.',
                ]
            );
        }

        // 5. Column QUOTE DRAFT: Pricing calculation in progress
        $inq5 = Inquiry::firstOrCreate(
            ['inquiry_number' => 'PBB/INQ/2026/10/0005'],
            [
                'assigned_user_id' => $adminId,
                'company_name' => 'PT. Sinar Semesta Engineering',
                'customer_name' => 'Dicky Darmawan',
                'phone_wa' => '081788223344',
                'email' => 'dicky.darmawan@sinarsemesta.com',
                'project_location' => 'Kawasan Industri Rungkut, Surabaya',
                'type' => 'bulk_cart',
                'source' => 'website_rfq',
                'status' => 'quote_draft',
                'first_contacted_at' => now()->subDays(2),
                'notes' => 'Boiler Feedwater High-Pressure Piping System. Pricing team verifying factory HPP discount from Kitz Valves principal.',
            ]
        );
        if ($inq5->wasRecentlyCreated) {
            $inq5->timestamps = false;
            $inq5->created_at = now()->subDays(2);
            $inq5->save();

            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq5->id, 'model_number' => '150SCLS-4IN'],
                [
                    'item_name' => 'Kitz Cast Carbon Steel Flanged Gate Valve 150WOG 4 inch',
                    'quantity' => 8,
                    'uom' => 'PCS',
                ]
            );
            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq5->id, 'model_number' => '10UTB-2IN'],
                [
                    'item_name' => 'Kitz Stainless Steel Ball Valve 3-Piece Full Bore 2 inch',
                    'quantity' => 16,
                    'uom' => 'PCS',
                ]
            );
        }

        // 6. Column QUOTE SENT / NEGOTIATION
        $inq6 = Inquiry::firstOrCreate(
            ['inquiry_number' => 'PBB/INQ/2026/10/0006'],
            [
                'assigned_user_id' => $adminId,
                'company_name' => 'PT. Jaya Konstruksi Manggala Pratama',
                'customer_name' => 'Ferry Gunawan, M.T.',
                'phone_wa' => '081299114455',
                'email' => 'ferry.gunawan@jayakonstruksi.com',
                'project_location' => 'LRT Station Koridor Cawang-Bekasi',
                'type' => 'single_direct',
                'source' => 'referral',
                'status' => 'quote_sent',
                'first_contacted_at' => now()->subDays(4),
                'notes' => 'Official quotation PBB/QUO/2026/10/0001 submitted. Awaiting procurement committee review.',
            ]
        );
        if ($inq6->wasRecentlyCreated) {
            $inq6->timestamps = false;
            $inq6->created_at = now()->subDays(4);
            $inq6->save();

            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq6->id, 'model_number' => 'LEG-P31-30050-HDG'],
                [
                    'item_name' => 'Legrand Cablofil P31 Cable Tray Perforated 300x50x3000mm HDG',
                    'quantity' => 120,
                    'uom' => 'LENGTH',
                ]
            );
        }

        // 7. Column WON / DEAL
        $inq7 = Inquiry::firstOrCreate(
            ['inquiry_number' => 'PBB/INQ/2026/10/0007'],
            [
                'assigned_user_id' => $adminId,
                'company_name' => 'PT. Adhi Karya (Persero) Tbk - Gedung Divisi',
                'customer_name' => 'Bpk. Wahyu Hidayat',
                'phone_wa' => '081311002299',
                'email' => 'wahyu.hidayat@adhikarya.co.id',
                'project_location' => 'Rumah Sakit Umum Vertikal Surabaya',
                'type' => 'bulk_cart',
                'source' => 'website_rfq',
                'status' => 'deal',
                'first_contacted_at' => now()->subDays(7),
                'notes' => 'PO Issued by Adhi Karya. Sales Order generated and ready for logistics dispatch.',
            ]
        );
        if ($inq7->wasRecentlyCreated) {
            $inq7->timestamps = false;
            $inq7->created_at = now()->subDays(8);
            $inq7->save();

            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq7->id, 'model_number' => 'BY239P-120W-6500K'],
                [
                    'item_name' => 'Philips SmartBright Highbay G3 BY239P LED150/CW PSU',
                    'quantity' => 80,
                    'uom' => 'UNIT',
                    'custom_notes' => 'Complete with suspension hook and safety lanyard cable.',
                ]
            );
        }

        // 8. Column LOST
        $inq8 = Inquiry::firstOrCreate(
            ['inquiry_number' => 'PBB/INQ/2026/10/0008'],
            [
                'assigned_user_id' => $adminId,
                'company_name' => 'PT. Mitra Mandiri Cemerlang',
                'customer_name' => 'Taufik Hidayat',
                'phone_wa' => '081288776655',
                'email' => 'taufik@mitramandiri.com',
                'project_location' => 'Kawasan Industri KIIC Karawang',
                'type' => 'single_direct',
                'source' => 'website_rfq',
                'status' => 'lost',
                'first_contacted_at' => now()->subDays(10),
                'notes' => 'Alasan pembatalan: Owner memutuskan penundaan proyek ekspansi pabrik ke tahun fiskal 2027 karena realokasi anggaran.',
            ]
        );
        if ($inq8->wasRecentlyCreated) {
            $inq8->timestamps = false;
            $inq8->created_at = now()->subDays(12);
            $inq8->save();

            InquiryItem::firstOrCreate(
                ['inquiry_id' => $inq8->id, 'model_number' => '0611253703'],
                [
                    'item_name' => 'Bosch Professional Rotary Hammer Drill GBH 2-26 DRE',
                    'quantity' => 5,
                    'uom' => 'UNIT',
                ]
            );
        }
    }
}
