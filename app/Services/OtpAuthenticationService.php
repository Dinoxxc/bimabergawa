<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendOtpEmailJob;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class OtpAuthenticationService
{
    public const OTP_TTL_SECONDS = 300; // 5 Menit

    public const RESEND_COOLDOWN_SECONDS = 60; // 60 Detik

    public const MAX_ATTEMPTS = 3; // Maksimal 3 kali percobaan

    public const LOCKOUT_DURATION_SECONDS = 600; // Kunci 10 Menit saat brute-force

    /**
     * Membangkitkan dan mengirimkan 6-digit CSPRNG OTP ke email terdaftar.
     * Menerapkan mitigasi User Enumeration Attack (TC-ERP-AUTH-009).
     */
    public static function generateOtp(string $email): array
    {
        $normalizedEmail = strtolower(trim($email));

        // Cek apakah email dalam status lockout brute-force
        if (self::isLocked($normalizedEmail)) {
            $lockoutRemaining = self::getLockoutRemaining($normalizedEmail);

            return [
                'status' => 'locked',
                'message' => "Terlalu banyak percobaan gagal. Silakan tunggu {$lockoutRemaining} detik sebelum meminta kode baru.",
                'remaining_seconds' => $lockoutRemaining,
            ];
        }

        // Cek apakah masih dalam jeda waktu cooldown 60 detik (TC-ERP-AUTH-003)
        $cooldownRemaining = self::getCooldownRemaining($normalizedEmail);
        if ($cooldownRemaining > 0) {
            return [
                'status' => 'cooldown',
                'message' => "Silakan tunggu {$cooldownRemaining} detik sebelum meminta pengiriman ulang kode OTP.",
                'remaining_seconds' => $cooldownRemaining,
            ];
        }

        $user = User::where('email', $normalizedEmail)->first();

        // Jika user tidak terdaftar atau non-aktif, tampilkan pesan generik (TC-ERP-AUTH-009)
        if (! $user || ! $user->is_active) {
            // Pasang cooldown tiruan untuk mencegah spamming kueri
            Cache::put("erp_otp_cooldown_{$normalizedEmail}", now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->timestamp, self::RESEND_COOLDOWN_SECONDS);

            return [
                'status' => 'sent',
                'email' => $normalizedEmail,
                'message' => 'Jika email terdaftar dalam sistem, kode OTP 6-digit telah dikirimkan ke kotak masuk Anda.',
                'cooldown_seconds' => self::RESEND_COOLDOWN_SECONDS,
                'expires_in_seconds' => self::OTP_TTL_SECONDS,
            ];
        }

        // Generate 6-Digit CSPRNG OTP
        $plainOtp = sprintf('%06d', random_int(100000, 999999));
        $hashedOtp = hash('sha256', $plainOtp);

        // Simpan hash token di cache backend selama tepat 300 detik (5 menit)
        Cache::put("erp_otp_{$normalizedEmail}", [
            'hash' => $hashedOtp,
            'attempts' => 0,
            'created_at' => now()->timestamp,
        ], self::OTP_TTL_SECONDS);

        // Pasang cooldown 60 detik
        Cache::put("erp_otp_cooldown_{$normalizedEmail}", now()->addSeconds(self::RESEND_COOLDOWN_SECONDS)->timestamp, self::RESEND_COOLDOWN_SECONDS);

        // Kirim email OTP melalui Job
        SendOtpEmailJob::dispatch($user, $plainOtp, self::OTP_TTL_SECONDS);

        return [
            'status' => 'sent',
            'email' => $normalizedEmail,
            'message' => 'Kode OTP 6-digit telah dikirimkan ke email Anda.',
            'cooldown_seconds' => self::RESEND_COOLDOWN_SECONDS,
            'expires_in_seconds' => self::OTP_TTL_SECONDS,
        ];
    }

    /**
     * Memverifikasi kode OTP yang dimasukkan pengguna.
     * Menerapkan mitigasi brute force (maks 3x) dan One-Time Burn (TC-ERP-AUTH-002, 010, 011, 012, 013).
     */
    public static function verifyOtp(string $email, string $otp): array
    {
        $normalizedEmail = strtolower(trim($email));
        $cleanOtp = trim($otp);

        // Cek status lockout brute-force (TC-ERP-AUTH-013)
        if (self::isLocked($normalizedEmail)) {
            $lockoutRemaining = self::getLockoutRemaining($normalizedEmail);

            return [
                'status' => 'locked',
                'remaining_attempts' => 0,
                'message' => "Sesi form terkunci karena batas percobaan habis. Silakan coba kembali dalam {$lockoutRemaining} detik.",
                'remaining_seconds' => $lockoutRemaining,
            ];
        }

        $cachedOtp = Cache::get("erp_otp_{$normalizedEmail}");

        // Cek kedaluwarsa atau token tidak ditemukan (TC-ERP-AUTH-011 & TC-ERP-AUTH-012)
        if (! $cachedOtp || ! isset($cachedOtp['hash'])) {
            return [
                'status' => 'expired',
                'remaining_attempts' => 0,
                'message' => 'Kode OTP telah kedaluwarsa atau tidak valid. Silakan minta kode baru.',
            ];
        }

        $attempts = (int) ($cachedOtp['attempts'] ?? 0);

        // Verifikasi kesesuaian hash token secara timing-attack safe
        $inputHash = hash('sha256', $cleanOtp);
        if (hash_equals($cachedOtp['hash'], $inputHash)) {
            // One-Time Burn Token: Bakar token langsung setelah sukses (TC-ERP-AUTH-002 & TC-ERP-AUTH-012)
            self::burnOtpToken($normalizedEmail);
            Cache::forget("erp_otp_cooldown_{$normalizedEmail}");
            Cache::forget("erp_otp_lockout_{$normalizedEmail}");

            $user = User::where('email', $normalizedEmail)->first();

            return [
                'status' => 'verified',
                'user' => $user,
                'message' => 'Verifikasi kode OTP berhasil.',
            ];
        }

        // Jika salah, inkremen counter percobaan
        $attempts++;
        $remainingAttempts = max(0, self::MAX_ATTEMPTS - $attempts);

        if ($remainingAttempts <= 0) {
            // Kunci akun selama 10 menit dan bakar token (TC-ERP-AUTH-013)
            self::burnOtpToken($normalizedEmail);
            Cache::put("erp_otp_lockout_{$normalizedEmail}", now()->addSeconds(self::LOCKOUT_DURATION_SECONDS)->timestamp, self::LOCKOUT_DURATION_SECONDS);

            return [
                'status' => 'locked',
                'remaining_attempts' => 0,
                'message' => 'Percobaan habis. Sesi OTP dibatalkan. Silakan minta kode baru setelah masa jeda keamanan berakhir.',
                'remaining_seconds' => self::LOCKOUT_DURATION_SECONDS,
            ];
        }

        // Perbarui sisa percobaan di cache dengan mempertahankan hash
        Cache::put("erp_otp_{$normalizedEmail}", [
            'hash' => $cachedOtp['hash'],
            'attempts' => $attempts,
            'created_at' => $cachedOtp['created_at'] ?? now()->timestamp,
        ], self::OTP_TTL_SECONDS);

        return [
            'status' => 'invalid',
            'remaining_attempts' => $remainingAttempts,
            'message' => "Kode OTP salah. Sisa percobaan: {$remainingAttempts}.",
        ];
    }

    /**
     * Membakar (menghapus) token OTP aktif di cache backend.
     */
    public static function burnOtpToken(string $email): void
    {
        Cache::forget('erp_otp_'.strtolower(trim($email)));
    }

    /**
     * Mendapatkan sisa waktu cooldown pengiriman ulang dalam detik.
     */
    public static function getCooldownRemaining(string $email): int
    {
        $targetTimestamp = Cache::get('erp_otp_cooldown_'.strtolower(trim($email)));
        if (! $targetTimestamp) {
            return 0;
        }

        return max(0, (int) ($targetTimestamp - now()->timestamp));
    }

    /**
     * Mengecek apakah email pengguna sedang berada dalam masa lockout brute-force.
     */
    public static function isLocked(string $email): bool
    {
        return Cache::has('erp_otp_lockout_'.strtolower(trim($email)));
    }

    /**
     * Mendapatkan sisa waktu lockout dalam detik.
     */
    public static function getLockoutRemaining(string $email): int
    {
        $targetTimestamp = Cache::get('erp_otp_lockout_'.strtolower(trim($email)));
        if (! $targetTimestamp) {
            return 0;
        }

        return max(0, (int) ($targetTimestamp - now()->timestamp));
    }
}
