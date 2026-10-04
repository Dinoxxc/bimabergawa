<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CommissionPayout;
use App\Models\DeliveryOrder;
use App\Models\FreelanceSales;
use App\Models\Inquiry;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CommercialSampleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@bimabergawa.com')->first() ?? User::first();
        $inquiries = Inquiry::take(4)->get();

        if ($inquiries->isEmpty()) {
            return;
        }

        // Quotations
        $quotes = [
            [
                'quotation_number' => 'PBB/QUO/2026/09/0001',
                'revision_number' => 0,
                'inquiry_id' => $inquiries[0]->id,
                'user_id' => $superAdmin->id,
                'customer_name' => 'Budi Santoso',
                'company_name' => 'PT. Waskita Karya (Persero) Tbk',
                'project_name' => 'LRT Jabodebek Phase 2 Panel Power Upgrade',
                'subtotal' => 245000000,
                'discount_amount' => 0,
                'dpp' => 245000000,
                'vat_rate_applied' => 11.00,
                'tax_amount' => 26950000,
                'grand_total' => 271950000,
                'currency' => 'IDR',
                'total_cost' => 198000000,
                'gross_profit_margin' => 19.18,
                'status' => 'approved',
                'expires_at' => Carbon::now()->addDays(14),
            ],
            [
                'quotation_number' => 'PBB/QUO/2026/09/0002',
                'revision_number' => 1,
                'inquiry_id' => $inquiries[1]->id,
                'user_id' => $superAdmin->id,
                'customer_name' => 'Hendra Wijaya',
                'company_name' => 'PT. Jaya Konstruksi Manggala Pratama',
                'project_name' => 'Data Center Cikarang Schneider Prisma iPM',
                'subtotal' => 520000000,
                'discount_amount' => 15000000,
                'dpp' => 505000000,
                'vat_rate_applied' => 11.00,
                'tax_amount' => 55550000,
                'grand_total' => 560550000,
                'currency' => 'IDR',
                'total_cost' => 445000000,
                'gross_profit_margin' => 11.88,
                'status' => 'sent',
                'expires_at' => Carbon::now()->addDays(21),
            ],
            [
                'quotation_number' => 'PBB/QUO/2026/09/0003',
                'revision_number' => 0,
                'inquiry_id' => $inquiries[2]->id,
                'user_id' => $superAdmin->id,
                'customer_name' => 'Ir. Bambang Sugiarto',
                'company_name' => 'PT. Total Bangun Persada Tbk',
                'project_name' => 'Apartemen Thamrin Nine HVAC Valves Sourcing',
                'subtotal' => 88000000,
                'discount_amount' => 0,
                'dpp' => 88000000,
                'vat_rate_applied' => 11.00,
                'tax_amount' => 9680000,
                'grand_total' => 97680000,
                'currency' => 'IDR',
                'total_cost' => 82000000,
                'gross_profit_margin' => 6.82,
                'status' => 'pending_director_approval',
                'expires_at' => Carbon::now()->addDays(7),
            ],
        ];

        foreach ($quotes as $qData) {
            Quotation::updateOrCreate(
                ['quotation_number' => $qData['quotation_number']],
                $qData
            );
        }

        $quote1 = Quotation::where('quotation_number', 'PBB/QUO/2026/09/0001')->first();
        $quote2 = Quotation::where('quotation_number', 'PBB/QUO/2026/09/0002')->first();

        // Sales Orders
        $so1 = SalesOrder::updateOrCreate(
            ['order_number' => 'PBB/SOR/2026/09/0001'],
            [
                'quotation_id' => $quote1->id,
                'customer_po_number' => 'PO-WK-2026-0881',
                'customer_po_date' => Carbon::now()->subDays(5),
                'project_name' => 'LRT Jabodebek Phase 2 Panel Power Upgrade',
                'status' => 'ready_to_ship',
                'vat_rate_applied' => 11.00,
                'total_amount' => 271950000,
                'currency' => 'IDR',
                'is_approved_by_director' => true,
                'approved_by' => $superAdmin->id,
                'approved_at' => Carbon::now()->subDays(4),
            ]
        );

        $so2 = SalesOrder::updateOrCreate(
            ['order_number' => 'PBB/SOR/2026/09/0002'],
            [
                'quotation_id' => $quote2->id,
                'customer_po_number' => 'PO-JK-9941-DC',
                'customer_po_date' => Carbon::now()->subDays(2),
                'project_name' => 'Data Center Cikarang Schneider Prisma iPM',
                'status' => 'processing',
                'vat_rate_applied' => 11.00,
                'total_amount' => 560550000,
                'currency' => 'IDR',
                'is_approved_by_director' => true,
                'approved_by' => $superAdmin->id,
                'approved_at' => Carbon::now()->subDays(1),
            ]
        );

        // Delivery Orders (Surat Jalan)
        DeliveryOrder::updateOrCreate(
            ['delivery_number' => 'PBB/DOR/2026/09/0001/01'],
            [
                'sales_order_id' => $so1->id,
                'courier_name' => 'Internal Fleet (PBB Logistics)',
                'driver_name' => 'Agus Priyono',
                'vehicle_plate_number' => 'B 9482 PBB',
                'tracking_number' => 'AWB-PBB-092601',
                'shipping_address' => 'Workshop LRT Jabodebek KM 12, Bekasi Timur',
                'recipient_pic_name' => 'Agung Wicaksono',
                'recipient_phone' => '081298834411',
                'total_packages' => 8,
                'total_weight_kg' => 450.00,
                'status' => 'delivered',
                'shipped_at' => Carbon::now()->subDays(2),
            ]
        );

        DeliveryOrder::updateOrCreate(
            ['delivery_number' => 'PBB/DOR/2026/09/0002/01'],
            [
                'sales_order_id' => $so2->id,
                'courier_name' => 'Dakota Cargo Ekspedisi',
                'driver_name' => 'Rahmat Hidayat',
                'vehicle_plate_number' => 'B 8122 KAA',
                'tracking_number' => 'DKT-88492019',
                'shipping_address' => 'Delta Silicon Industrial Park Kav. 4B, Cikarang',
                'recipient_pic_name' => 'Bayu Permana',
                'recipient_phone' => '081377889922',
                'total_packages' => 14,
                'total_weight_kg' => 920.00,
                'status' => 'shipped',
                'shipped_at' => Carbon::now()->subHours(6),
            ]
        );

        // Invoices
        Invoice::updateOrCreate(
            ['invoice_number' => 'PBB/INV/2026/09/0001'],
            [
                'sales_order_id' => $so1->id,
                'quotation_id' => $quote1->id,
                'customer_name' => 'Budi Santoso',
                'company_name' => 'PT. Waskita Karya (Persero) Tbk',
                'faktur_pajak_number' => '010.002-26.88392011',
                'invoice_date' => Carbon::now()->subDays(3),
                'due_date' => Carbon::now()->addDays(27),
                'subtotal' => 245000000,
                'vat_rate_applied' => 11.00,
                'tax_amount' => 26950000,
                'grand_total' => 271950000,
                'paid_amount' => 271950000,
                'currency' => 'IDR',
                'status' => 'paid',
                'payment_method' => 'bank_transfer',
                'paid_at' => Carbon::now()->subDays(1),
            ]
        );

        Invoice::updateOrCreate(
            ['invoice_number' => 'PBB/INV/2026/09/0002'],
            [
                'sales_order_id' => $so2->id,
                'quotation_id' => $quote2->id,
                'customer_name' => 'Hendra Wijaya',
                'company_name' => 'PT. Jaya Konstruksi Manggala Pratama',
                'faktur_pajak_number' => '010.002-26.88392012',
                'invoice_date' => Carbon::now()->subDays(1),
                'due_date' => Carbon::now()->addDays(29),
                'subtotal' => 505000000,
                'vat_rate_applied' => 11.00,
                'tax_amount' => 55550000,
                'grand_total' => 560550000,
                'paid_amount' => 100000000,
                'currency' => 'IDR',
                'status' => 'partially_paid',
                'payment_method' => 'bank_transfer',
            ]
        );

        // Commission Payouts (if freelance sales exists)
        $agent = FreelanceSales::first();
        if ($agent) {
            CommissionPayout::updateOrCreate(
                ['payout_number' => 'PBB/COM/2026/09/0001'],
                [
                    'freelance_sales_id' => $agent->id,
                    'sales_order_id' => $so1->id,
                    'amount' => 5439000,
                    'type' => 'paid',
                    'status' => 'paid',
                    'paid_at' => Carbon::now()->subDay(),
                    'notes' => 'Komisi deal LRT Jabodebek Phase 2 (Tier Gold 2.0%)',
                ]
            );

            CommissionPayout::updateOrCreate(
                ['payout_number' => 'PBB/COM/2026/09/0002'],
                [
                    'freelance_sales_id' => $agent->id,
                    'sales_order_id' => $so2->id,
                    'amount' => 11211000,
                    'type' => 'requested',
                    'status' => 'payable',
                    'notes' => 'Komisi deal Data Center Cikarang (Pending Director Approval)',
                ]
            );
        }
    }
}
