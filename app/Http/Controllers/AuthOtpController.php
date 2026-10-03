<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\OtpAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthOtpController extends Controller
{
    /**
     * Menampilkan halaman login passwordless email OTP.
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return (new PanelRedirectionController)->index($request);
        }

        return view('auth.otp-login', [
            'email' => $request->session()->get('otp_email', ''),
            'step' => $request->session()->get('otp_step', 'email'),
            'cooldownSeconds' => $request->session()->get('otp_cooldown', 0),
        ]);
    }

    /**
     * Memproses permintaan kode OTP ke email.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $result = OtpAuthenticationService::generateOtp($validated['email']);

        if ($result['status'] === 'locked') {
            if ($request->wantsJson()) {
                return response()->json($result, 429);
            }

            return back()->withInput()->withErrors(['email' => $result['message']]);
        }

        if ($result['status'] === 'cooldown') {
            if ($request->wantsJson()) {
                return response()->json($result, 429);
            }

            return back()->withInput()->withErrors(['email' => $result['message']]);
        }

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        return redirect()->route('auth.otp.login')
            ->with('otp_step', 'verify')
            ->with('otp_email', $result['email'])
            ->with('otp_cooldown', $result['cooldown_seconds'])
            ->with('status', $result['message']);
    }

    /**
     * Memverifikasi kode OTP dan memulai sesi login pengguna.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $result = OtpAuthenticationService::verifyOtp($validated['email'], $validated['otp']);

        if ($result['status'] === 'verified') {
            Auth::login($result['user'], remember: true);

            // Mencegah Session Fixation Attack (TC-ERP-AUTH-014)
            $request->session()->regenerate();
            $request->session()->forget(['otp_step', 'otp_email', 'otp_cooldown']);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'verified',
                    'redirect' => (new PanelRedirectionController)->index($request)->getTargetUrl(),
                ]);
            }

            return (new PanelRedirectionController)->index($request);
        }

        if ($request->wantsJson()) {
            $statusHttp = ($result['status'] === 'locked') ? 429 : 422;

            return response()->json($result, $statusHttp);
        }

        return back()
            ->withInput(['email' => $validated['email']])
            ->with('otp_step', 'verify')
            ->with('otp_email', $validated['email'])
            ->withErrors(['otp' => $result['message']]);
    }

    /**
     * Memproses logout pengguna dan menghancurkan sesi aktif.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Pembersihan sesi total (TC-ERP-AUTH-008)
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.otp.login');
    }
}
