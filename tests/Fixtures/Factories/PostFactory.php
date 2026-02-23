<?php

namespace Cesargb\MorphCleaner\Tests\Fixtures\Factories;

use Cesargb\MorphCleaner\Tests\Fixtures\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
        ];
    }
}
