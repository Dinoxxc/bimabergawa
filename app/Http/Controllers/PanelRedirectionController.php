<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanelRedirectionController extends Controller
{
    /**
     * Menangani auto-redirection cerdas rute root GET / pada subdomain app.bimabergawa.com:
     * - Tamu (Guest) -> dialihkan ke /login
     * - Mitra Agen Freelance -> dialihkan ke /mitra
     * - Staf Internal (super_admin, sales_pic, finance, logistics) -> dialihkan ke /admin
     */
    public function index(Request $request): RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->to('/login');
        }

        /** @var User $user */
        $user = Auth::user();

        // Cek peran Spatie RBAC
        $isAgent = $user->hasAnyRole(['freelance_sales', 'mitra_agen', 'freelance_agent']) || $user->freelance_sales_id !== null;
        $isStaff = $user->hasAnyRole(['super_admin', 'sales_pic', 'finance', 'logistics']);

        if ($isAgent && ! $isStaff) {
            return redirect()->to('/mitra');
        }

        return redirect()->to('/admin');
    }
}
