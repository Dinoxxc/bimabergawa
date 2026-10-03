<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Jobs\SendOtpEmailJob;
use App\Models\User;
use App\Services\OtpAuthenticationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordlessOtpAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        Cache::flush();
    }

    /**
     * TC-ERP-AUTH-001: Permintaan Token OTP dengan Alamat Email Internal Terdaftar.
     */
    public function test_tc_erp_auth_001_request_otp_with_valid_registered_email(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'sales.pic@bimabergawa.com',
            'is_active' => true,
        ]);
        $user->assignRole('sales_pic');

        $response = $this->post(route('auth.otp.send'), [
            'email' => 'sales.pic@bimabergawa.com',
        ]);

        $response->assertRedirect(route('auth.otp.login'));
        $response->assertSessionHas('otp_step', 'verify');
        $response->assertSessionHas('otp_email', 'sales.pic@bimabergawa.com');

        // Verifikasi token OTP tersimpan di Cache (di-hash SHA-256)
        $cached = Cache::get('erp_otp_sales.pic@bimabergawa.com');
        $this->assertNotNull($cached);
        $this->assertArrayHasKey('hash', $cached);
        $this->assertEquals(0, $cached['attempts']);

        // Verifikasi Job pengiriman email OTP di-dispatch
        Queue::assertPushed(SendOtpEmailJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });
    }

    /**
     * TC-ERP-AUTH-002: Verifikasi Token OTP Valid & Regenerasi Sesi.
     */
    public function test_tc_erp_auth_002_verify_valid_otp_and_regenerate_session(): void
    {
        $user = User::factory()->create([
            'email' => 'sales.pic@bimabergawa.com',
            'is_active' => true,
        ]);
        $user->assignRole('sales_pic');

        // Bikin OTP di cache
        $otp = '482910';
        Cache::put('erp_otp_sales.pic@bimabergawa.com', [
            'hash' => hash('sha256', $otp),
            'attempts' => 0,
            'created_at' => now()->timestamp,
        ], 300);

        $response = $this->post(route('auth.otp.verify'), [
            'email' => 'sales.pic@bimabergawa.com',
            'otp' => $otp,
        ]);

        // Verifikasi redirect ke dashboard internal staf
        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);

        // Verifikasi One-Time Burn: Cache OTP dihapus
        $this->assertNull(Cache::get('erp_otp_sales.pic@bimabergawa.com'));
    }

    /**
     * TC-ERP-AUTH-003: Penegakan Jeda Waktu Tunggu Kirim Ulang (Cooldown 60 Detik).
     */
    public function test_tc_erp_auth_003_resend_cooldown_enforcement(): void
    {
        $user = User::factory()->create([
            'email' => 'finance@bimabergawa.com',
            'is_active' => true,
        ]);
        $user->assignRole('finance');

        // Request pertama
        $firstResponse = $this->postJson(route('auth.otp.send'), [
            'email' => 'finance@bimabergawa.com',
        ]);
        $firstResponse->assertOk();
        $this->assertEquals('sent', $firstResponse->json('status'));

        // Request kedua langsung dalam jeda cooldown
        $secondResponse = $this->postJson(route('auth.otp.send'), [
            'email' => 'finance@bimabergawa.com',
        ]);
        $secondResponse->assertStatus(429);
        $this->assertEquals('cooldown', $secondResponse->json('status'));
        $this->assertGreaterThan(0, $secondResponse->json('remaining_seconds'));
    }

    /**
     * TC-ERP-AUTH-004: Otorisasi Hak Akses Peran Super Admin.
     */
    public function test_tc_erp_auth_004_super_admin_authorization(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super_admin');

        $adminPanel = Filament::getPanel('admin');
        $mitraPanel = Filament::getPanel('mitra');

        $this->assertTrue($admin->canAccessPanel($adminPanel));
        $this->assertTrue($admin->canAccessPanel($mitraPanel));

        $this->actingAs($admin);
        $response = $this->get(route('filament.admin.pages.dashboard'));
        $response->assertOk();
    }

    /**
     * TC-ERP-AUTH-005: Otorisasi Hak Akses Peran Sales PIC.
     */
    public function test_tc_erp_auth_005_sales_pic_authorization(): void
    {
        $sales = User::factory()->create(['is_active' => true]);
        $sales->assignRole('sales_pic');

        $adminPanel = Filament::getPanel('admin');
        $mitraPanel = Filament::getPanel('mitra');

        $this->assertTrue($sales->canAccessPanel($adminPanel));
        $this->assertFalse($sales->canAccessPanel($mitraPanel));

        $this->actingAs($sales);
        $responseAdmin = $this->get(route('filament.admin.pages.dashboard'));
        $responseAdmin->assertOk();

        $responseMitra = $this->get(route('filament.mitra.pages.dashboard'));
        $responseMitra->assertForbidden();
    }

    /**
     * TC-ERP-AUTH-006: Otorisasi Hak Akses Peran Finance.
     */
    public function test_tc_erp_auth_006_finance_authorization(): void
    {
        $finance = User::factory()->create(['is_active' => true]);
        $finance->assignRole('finance');

        $adminPanel = Filament::getPanel('admin');
        $mitraPanel = Filament::getPanel('mitra');

        $this->assertTrue($finance->canAccessPanel($adminPanel));
        $this->assertFalse($finance->canAccessPanel($mitraPanel));

        $this->actingAs($finance);
        $responseAdmin = $this->get(route('filament.admin.pages.dashboard'));
        $responseAdmin->assertOk();

        $responseMitra = $this->get(route('filament.mitra.pages.dashboard'));
        $responseMitra->assertForbidden();
    }

    /**
     * TC-ERP-AUTH-007: Otorisasi Hak Akses Peran Logistik.
     */
    public function test_tc_erp_auth_007_logistics_authorization(): void
    {
        $logistics = User::factory()->create(['is_active' => true]);
        $logistics->assignRole('logistics');

        $adminPanel = Filament::getPanel('admin');
        $mitraPanel = Filament::getPanel('mitra');

        $this->assertTrue($logistics->canAccessPanel($adminPanel));
        $this->assertFalse($logistics->canAccessPanel($mitraPanel));

        $this->actingAs($logistics);
        $responseAdmin = $this->get(route('filament.admin.pages.dashboard'));
        $responseAdmin->assertOk();

        $responseMitra = $this->get(route('filament.mitra.pages.dashboard'));
        $responseMitra->assertForbidden();
    }

    /**
     * TC-ERP-AUTH-008: Pembersihan Sesi saat Logout Sukses.
     */
    public function test_tc_erp_auth_008_session_cleanup_on_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('sales_pic');

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post(route('auth.otp.logout'));

        $this->assertGuest();
        $response->assertRedirect(route('auth.otp.login'));
    }

    /**
     * TC-ERP-AUTH-009: Permintaan OTP dengan Email yang Tidak Terdaftar (Anti User-Enumeration).
     */
    public function test_tc_erp_auth_009_unregistered_email_generic_response(): void
    {
        Queue::fake();

        $response = $this->postJson(route('auth.otp.send'), [
            'email' => 'penyusup@external.com',
        ]);

        $response->assertOk();
        $this->assertEquals('sent', $response->json('status'));
        $this->assertStringContainsString('If this email is registered', $response->json('message'));

        // Tidak ada job atau email keluar
        Queue::assertNothingPushed();
        $this->assertNull(Cache::get('erp_otp_penyusup@external.com'));
    }

    /**
     * TC-ERP-AUTH-010: Verifikasi dengan Token OTP yang Salah.
     */
    public function test_tc_erp_auth_010_wrong_otp_attempts_tracking(): void
    {
        $user = User::factory()->create([
            'email' => 'sales.pic@bimabergawa.com',
            'is_active' => true,
        ]);
        $user->assignRole('sales_pic');

        Cache::put('erp_otp_sales.pic@bimabergawa.com', [
            'hash' => hash('sha256', '123456'),
            'attempts' => 0,
            'created_at' => now()->timestamp,
        ], 300);

        $response = $this->postJson(route('auth.otp.verify'), [
            'email' => 'sales.pic@bimabergawa.com',
            'otp' => '000000',
        ]);

        $response->assertStatus(422);
        $this->assertEquals('invalid', $response->json('status'));
        $this->assertEquals(2, $response->json('remaining_attempts'));
        $this->assertGuest();

        // Attempts incremented in cache
        $cached = Cache::get('erp_otp_sales.pic@bimabergawa.com');
        $this->assertEquals(1, $cached['attempts']);
    }

    /**
     * TC-ERP-AUTH-011: Verifikasi dengan Token OTP yang Telah Kedaluwarsa (> 5 Menit).
     */
    public function test_tc_erp_auth_011_expired_otp_rejection(): void
    {
        $user = User::factory()->create([
            'email' => 'sales.pic@bimabergawa.com',
            'is_active' => true,
        ]);
        $user->assignRole('sales_pic');

        // Tidak ada data di cache (sudah expired)
        $response = $this->postJson(route('auth.otp.verify'), [
            'email' => 'sales.pic@bimabergawa.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(422);
        $this->assertEquals('expired', $response->json('status'));
        $this->assertGuest();
    }

    /**
     * TC-ERP-AUTH-012: Penggunaan Ulang Token OTP yang Sudah Terpakai (Replay Attack).
     */
    public function test_tc_erp_auth_012_otp_replay_attack_prevention(): void
    {
        $user = User::factory()->create([
            'email' => 'sales.pic@bimabergawa.com',
            'is_active' => true,
        ]);
        $user->assignRole('sales_pic');

        $otp = '555666';
        Cache::put('erp_otp_sales.pic@bimabergawa.com', [
            'hash' => hash('sha256', $otp),
            'attempts' => 0,
            'created_at' => now()->timestamp,
        ], 300);

        // Percobaan pertama: Sukses
        $res1 = $this->postJson(route('auth.otp.verify'), [
            'email' => 'sales.pic@bimabergawa.com',
            'otp' => $otp,
        ]);
        $res1->assertOk();
        $this->assertEquals('verified', $res1->json('status'));

        // Logout
        $this->post(route('auth.otp.logout'));

        // Percobaan kedua dengan token yang sama persis: Ditolak karena token sudah dibakar (burned)
        $res2 = $this->postJson(route('auth.otp.verify'), [
            'email' => 'sales.pic@bimabergawa.com',
            'otp' => $otp,
        ]);
        $res2->assertStatus(422);
        $this->assertEquals('expired', $res2->json('status'));
        $this->assertGuest();
    }

    /**
     * TC-ERP-AUTH-013: Penegakan Batas Brute-Force OTP (Throttle 3 Attempts Lockout).
     */
    public function test_tc_erp_auth_013_brute_force_lockout_after_three_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'target@bimabergawa.com',
            'is_active' => true,
        ]);
        $user->assignRole('sales_pic');

        $correctOtp = '999111';
        Cache::put('erp_otp_target@bimabergawa.com', [
            'hash' => hash('sha256', $correctOtp),
            'attempts' => 0,
            'created_at' => now()->timestamp,
        ], 300);

        // Percobaan 1: Salah
        $r1 = $this->postJson(route('auth.otp.verify'), [
            'email' => 'target@bimabergawa.com',
            'otp' => '111111',
        ]);
        $r1->assertStatus(422);
        $this->assertEquals(2, $r1->json('remaining_attempts'));

        // Percobaan 2: Salah
        $r2 = $this->postJson(route('auth.otp.verify'), [
            'email' => 'target@bimabergawa.com',
            'otp' => '222222',
        ]);
        $r2->assertStatus(422);
        $this->assertEquals(1, $r2->json('remaining_attempts'));

        // Percobaan 3: Salah -> Memicu lockout
        $r3 = $this->postJson(route('auth.otp.verify'), [
            'email' => 'target@bimabergawa.com',
            'otp' => '333333',
        ]);
        $r3->assertStatus(429);
        $this->assertEquals('locked', $r3->json('status'));

        // Verifikasi token OTP dihapus dan status lockout aktif
        $this->assertNull(Cache::get('erp_otp_target@bimabergawa.com'));
        $this->assertTrue(OtpAuthenticationService::isLocked('target@bimabergawa.com'));

        // Percobaan 4 sekalipun dengan kode yang benar tetap ditolak karena lockout
        $r4 = $this->postJson(route('auth.otp.verify'), [
            'email' => 'target@bimabergawa.com',
            'otp' => $correctOtp,
        ]);
        $r4->assertStatus(429);
        $this->assertEquals('locked', $r4->json('status'));
    }

    /**
     * TC-ERP-AUTH-014: Pencegahan Eksploitasi Session Fixation.
     */
    public function test_tc_erp_auth_014_session_fixation_prevention(): void
    {
        $user = User::factory()->create([
            'email' => 'sales.pic@bimabergawa.com',
            'is_active' => true,
        ]);
        $user->assignRole('sales_pic');

        $otp = '777888';
        Cache::put('erp_otp_sales.pic@bimabergawa.com', [
            'hash' => hash('sha256', $otp),
            'attempts' => 0,
            'created_at' => now()->timestamp,
        ], 300);

        // Dapatkan Session ID awal
        $this->get(route('auth.otp.login'));
        $initialSessionId = session()->getId();

        // Login verifikasi OTP
        $this->post(route('auth.otp.verify'), [
            'email' => 'sales.pic@bimabergawa.com',
            'otp' => $otp,
        ]);

        $newSessionId = session()->getId();

        // Session ID harus berubah setelah login berhasil
        $this->assertNotEmpty($initialSessionId);
        $this->assertNotEmpty($newSessionId);
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }

    /**
     * TC-ERP-AUTH-015: Penegakan Atribut Keamanan Cookie Sesi & Security Headers.
     */
    public function test_tc_erp_auth_015_security_headers_enforcement(): void
    {
        $response = $this->get(route('auth.otp.login'));

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    /**
     * TC-ERP-AUTH-017: Auto-Redirect Rute Root (GET /) Berbasis Peran (Option B Dispatch).
     */
    public function test_tc_erp_auth_017_root_auto_redirect_based_on_role(): void
    {
        // 1. Pengguna Anonim (Tamu) -> Dialihkan ke form login
        $guestResponse = $this->get(route('erp.root'));
        $guestResponse->assertRedirect('/login');

        // 2. Staf Internal (Sales PIC) -> Dialihkan ke /admin
        $salesUser = User::factory()->create(['is_active' => true]);
        $salesUser->assignRole('sales_pic');

        $this->actingAs($salesUser);
        $salesResponse = $this->get(route('erp.root'));
        $salesResponse->assertRedirect('/admin');

        // 3. Staf Internal (Super Admin) -> Dialihkan ke /admin
        $adminUser = User::factory()->create(['is_active' => true]);
        $adminUser->assignRole('super_admin');

        $this->actingAs($adminUser);
        $adminResponse = $this->get(route('erp.root'));
        $adminResponse->assertRedirect('/admin');

        // 4. Mitra Agen Freelance -> Dialihkan ke /mitra
        $agentUser = User::factory()->create(['is_active' => true]);
        $agentUser->assignRole('freelance_sales');

        $this->actingAs($agentUser);
        $agentResponse = $this->get(route('erp.root'));
        $agentResponse->assertRedirect('/mitra');
    }
}
