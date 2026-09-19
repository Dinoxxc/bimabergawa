<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence();

        return [
            'article_category_id' => ArticleCategory::factory(),
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'featured_image_path' => null,
            'reading_time_minutes' => 3,
            'is_published' => true,
            'published_at' => now(),
            'meta_description' => fake()->sentence(),
        ];
    }
}
