<?php

namespace Cesargb\MorphCleaner\Tests\Fixtures\Factories;

use Cesargb\MorphCleaner\Tests\Fixtures\Models\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    protected $model = Image::class;

    public function definition(): array
    {
        return [
            'url' => fake()->imageUrl(),
            'imageable_id' => null,
            'imageable_type' => null,
        ];
    }
}
