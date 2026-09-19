<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\InternalComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalComment>
 */
class InternalCommentFactory extends Factory
{
    protected $model = InternalComment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'commentable_type' => 'inquiry',
            'commentable_id' => Inquiry::factory(),
            'comment' => fake()->paragraph(),
            'type' => 'internal',
            'mentioned_user_ids' => null,
            'attachment_path' => null,
        ];
    }
}
