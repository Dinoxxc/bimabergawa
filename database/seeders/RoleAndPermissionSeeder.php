<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Granular permissions
        $permissions = [
            // Inquiries & CRM
            'view_inquiries',
            'manage_inquiries',
            'assign_inquiries',
            // Quotations & Pricing
            'create_quotations',
            'view_quotations',
            'view_hpp_margin',
            'approve_quotations',
            'approve_quotation_margin',
            // Sales Orders
            'create_sales_orders',
            'view_sales_orders',
            'approve_high_value_so',
            // Delivery Orders & Logistics
            'create_delivery_orders',
            'view_delivery_orders',
            'dispatch_delivery_orders',
            // Invoices & Billing
            'manage_invoices',
            'view_invoices',
            'verify_invoice_payment',
            // Products & Brands
            'manage_products',
            'import_products',
            'view_cost_price',
            // Financials & Commissions
            'view_financial_reports',
            'process_payouts',
            // Partners & Portal
            'manage_partners',
            'access_mitra_portal',
            // System & Audit
            'manage_system_settings',
            'manage_commercial_tax',
            'view_audit_logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 2. 5 Official Spatie RBAC Roles (sesuai AGENTS.md Tabel 4)
        $roleSuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $roleSuperAdmin->syncPermissions(Permission::all());

        $roleSales = Role::firstOrCreate(['name' => 'sales_pic', 'guard_name' => 'web']);
        $roleSales->syncPermissions([
            'view_inquiries',
            'manage_inquiries',
            'assign_inquiries',
            'create_quotations',
            'view_quotations',
            'view_hpp_margin',
            'create_sales_orders',
            'view_sales_orders',
            'view_delivery_orders',
            'view_invoices',
            'manage_products',
            'import_products',
            'view_cost_price',
        ]);

        $roleFinance = Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);
        $roleFinance->syncPermissions([
            'view_inquiries',
            'view_quotations',
            'view_hpp_margin',
            'view_sales_orders',
            'view_delivery_orders',
            'manage_invoices',
            'view_invoices',
            'verify_invoice_payment',
            'view_financial_reports',
            'process_payouts',
            'manage_commercial_tax',
        ]);

        $roleLogistics = Role::firstOrCreate(['name' => 'logistics', 'guard_name' => 'web']);
        $roleLogistics->syncPermissions([
            'view_sales_orders',
            'create_delivery_orders',
            'view_delivery_orders',
            'dispatch_delivery_orders',
        ]);

        $roleMitra = Role::firstOrCreate(['name' => 'freelance_sales', 'guard_name' => 'web']);
        $roleMitra->syncPermissions([
            'access_mitra_portal',
        ]);
    }
}
