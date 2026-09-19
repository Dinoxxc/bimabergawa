<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public static function log(
        string $logName,
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = []
    ): self {
        return static::create([
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'causer_type' => $causer ? $causer->getMorphClass() : null,
            'causer_id' => $causer ? $causer->getKey() : null,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
