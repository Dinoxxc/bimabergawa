<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DocumentVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DocumentVerificationController extends Controller
{
    /**
     * Handle public document authenticity verification.
     */
    public function verify(Request $request, string $type, string $hash): View
    {
        $verificationData = DocumentVerificationService::verify($type, $hash);

        return view('public.verify-document', [
            'type' => $type,
            'hash' => $hash,
            'isValid' => $verificationData !== null,
            'doc' => $verificationData,
        ]);
    }
}
