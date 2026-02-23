<?php

namespace Cesargb\ModelToolkit\Tests\Fixtures\Factories;

use Cesargb\ModelToolkit\Tests\Fixtures\Models\Post;
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
