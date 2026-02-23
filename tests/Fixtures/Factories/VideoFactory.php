<?php

namespace Cesargb\ModelToolkit\Tests\Fixtures\Factories;

use Cesargb\ModelToolkit\Tests\Fixtures\Models\Video;
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
