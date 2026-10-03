<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\SendOtpLoginEmail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otp,
        public int $ttlSeconds = 300
    ) {}

    /**
     * Eksekusi pengiriman email kode OTP login ke pengguna.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new SendOtpLoginEmail($this->user, $this->otp, $this->ttlSeconds)
        );
    }
}
