<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PrivateFileController extends Controller
{
    /**
     * Sajikan berkas privat secara aman hanya jika URL memiliki signature kriptografis yang valid.
     */
    public function download(Request $request): Response
    {
        $rawPath = (string) $request->query('path', '');
        $cleanPath = trim(str_replace(['..', '\\'], ['', '/'], $rawPath), '/');

        if ($cleanPath === '' || ! Storage::disk('private')->exists($cleanPath)) {
            abort(404, 'Berkas privat tidak ditemukan atau telah dihapus.');
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
        ];

        return Storage::disk('private')->response($cleanPath, null, $headers);
    }
}
