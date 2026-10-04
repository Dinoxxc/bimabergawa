<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FreelanceSales;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AgentRegistrationController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran mitra agen freelance.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.agent-register');
    }

    /**
     * Proses pendaftaran mandiri mitra agen freelance.
     * Menerapkan enkripsi data sensitif (UU PDP No. 27/2022),
     * validasi format NIK 16 digit numerik, dan penyimpanan KTP di storage privat.
     */
    public function register(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'nik' => [
                'required',
                'regex:/^[0-9]{16}$/',
            ],
            'npwp_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_account_name' => ['required', 'string', 'max:150'],
            'industry_focus' => ['nullable', 'string', 'max:200'],
            'ktp_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'nik.regex' => 'Nomor Induk Kependudukan (NIK) wajib terdiri dari 16 digit angka numerik',
            'email.unique' => 'Alamat email ini telah terdaftar pada sistem',
            'ktp_file.mimes' => 'Format berkas KTP wajib berupa JPG, PNG, atau PDF',
            'ktp_file.max' => 'Ukuran berkas KTP maksimal 5 MB',
        ]);

        $agent = DB::transaction(function () use ($request, $validated) {
            // Simpan berkas KTP di disk privat (TC-AGN-REG-011)
            $ktpFile = $request->file('ktp_file');
            $ktpFilename = sprintf('ktp_%s_%s.%s', Str::uuid(), time(), $ktpFile->getClientOriginalExtension());
            $ktpPath = 'private/identity/'.$ktpFilename;

            Storage::disk('local_private')->putFileAs('identity', $ktpFile, $ktpFilename);

            // Buat akun user dasar (status inaktif hingga disetujui Admin)
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make(Str::random(32)),
                'is_active' => false,
            ]);

            // Generate referral code resmi PBB (AGT-YYYYMMDD-XXXX)
            $count = FreelanceSales::count() + 1;
            $referralCode = sprintf('AGT-%s-%04d', date('Ymd'), $count);

            // Buat profil mitra agen dengan enkripsi database otomatis (TC-AGN-REG-002, TC-AGN-REG-003)
            $agent = FreelanceSales::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'referral_code' => $referralCode,
                'nik' => $validated['nik'],
                'npwp_number' => $validated['npwp_number'] ?? null,
                'ktp_file_path' => $ktpPath,
                'bank_name' => $validated['bank_name'],
                'bank_account_number' => $validated['bank_account_number'],
                'bank_account_name' => $validated['bank_account_name'],
                'industry_focus' => $validated['industry_focus'] ?? null,
                'status' => 'pending_review',
            ]);

            $user->update(['freelance_sales_id' => $agent->id]);

            // Audit Log pendaftaran agen (TC-AGN-REG-016)
            ActivityLog::create([
                'user_id' => $user->id,
                'causer_type' => User::class,
                'causer_id' => $user->id,
                'subject_type' => FreelanceSales::class,
                'subject_id' => $agent->id,
                'event' => 'agent_registered',
                'description' => sprintf('Freelance partner [%s] submitted registration application (%s)', $agent->name, $referralCode),
            ]);

            return $agent;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi oleh tim Admin PBB.',
                'data' => [
                    'agent_id' => $agent->id,
                    'referral_code' => $agent->referral_code,
                    'status' => $agent->status,
                ],
            ], 201);
        }

        return redirect()->route('login')->with('success', 'Pendaftaran berhasil diajukan. Akun Anda sedang dalam proses verifikasi identitas oleh tim Admin PT. Panca Bima Bergawa.');
    }
}
