<?php

namespace Cesargb\MorphCleaner\Tests\Fixtures\Factories;

use Cesargb\MorphCleaner\Tests\Fixtures\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
        ];
    }
}
