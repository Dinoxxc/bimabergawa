<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpNavigationResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->superAdmin = User::factory()->create([
            'email' => 'admin.erp@bimabergawa.com',
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_executive_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get('http://app.bimabergawa.test/admin');

        $response->assertSuccessful();
        $response->assertSee('Commercial Intelligence');
    }

    public function test_all_erp_resource_index_pages_render_successfully(): void
    {
        $urls = [
            'http://app.bimabergawa.test/admin/inquiries',
            'http://app.bimabergawa.test/admin/quotations',
            'http://app.bimabergawa.test/admin/sales-orders',
            'http://app.bimabergawa.test/admin/delivery-orders',
            'http://app.bimabergawa.test/admin/invoices',
            'http://app.bimabergawa.test/admin/commission-payouts',
            'http://app.bimabergawa.test/admin/products',
            'http://app.bimabergawa.test/admin/brands',
            'http://app.bimabergawa.test/admin/categories',
            'http://app.bimabergawa.test/admin/suppliers',
            'http://app.bimabergawa.test/admin/system-settings',
            'http://app.bimabergawa.test/admin/users',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertSuccessful();
        }
    }
}
