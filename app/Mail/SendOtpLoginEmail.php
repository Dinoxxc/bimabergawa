<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpLoginEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $otp,
        public readonly int $validitySeconds = 300,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'noreply@bimabergawa.com'),
                'PT. Panca Bima Bergawa - ERP Security'
            ),
            subject: 'Kode Verifikasi OTP Masuk Sistem ERP [PT. Panca Bima Bergawa]',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.otp',
            with: [
                'user' => $this->user,
                'otp' => $this->otp,
                'validityMinutes' => (int) round($this->validitySeconds / 60),
            ],
        );
    }
}
