<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'document_type',
        'year_month',
        'current_sequence',
    ];

    protected function casts(): array
    {
        return [
            'current_sequence' => 'integer',
        ];
    }
}
