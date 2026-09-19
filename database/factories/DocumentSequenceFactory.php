<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentSequence>
 */
class DocumentSequenceFactory extends Factory
{
    protected $model = DocumentSequence::class;

    public function definition(): array
    {
        return [
            'document_type' => fake()->randomElement(['INQ', 'QUO', 'SO', 'DO', 'INV', 'COM']),
            'year_month' => date('Ym'),
            'current_sequence' => fake()->numberBetween(1, 50),
        ];
    }
}
