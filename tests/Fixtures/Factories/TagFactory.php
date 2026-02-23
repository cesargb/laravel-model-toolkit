<?php

namespace Cesargb\MorphCleaner\Tests\Fixtures\Factories;

use Cesargb\MorphCleaner\Tests\Fixtures\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
