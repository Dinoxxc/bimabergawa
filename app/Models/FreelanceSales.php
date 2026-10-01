<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FreelanceSales extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commission_tier_id',
        'user_id',
        'name',
        'referral_code',
        'nik',
        'npwp_number',
        'ktp_file_path',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'industry_focus',
        'status',
        'rejection_reason',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'npwp_number' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     * Melindungi data pribadi sensitif agen (UU PDP No. 27/2022) saat serialisasi JSON.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'nik',
        'npwp_number',
        'bank_account_number',
        'ktp_file_path',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(CommissionTier::class, 'commission_tier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(CommissionPayout::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(InternalComment::class, 'commentable');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
}
