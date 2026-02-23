<?php

namespace Cesargb\ModelToolkit\Tests\Fixtures\Factories;

use Cesargb\ModelToolkit\Tests\Fixtures\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'body' => fake()->paragraph(),
            'commentable_id' => null,
            'commentable_type' => null,
        ];
    }
}
