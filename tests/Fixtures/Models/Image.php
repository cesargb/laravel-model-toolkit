<?php

namespace Cesargb\ModelToolkit\Tests\Fixtures\Models;

use Cesargb\ModelToolkit\Tests\Fixtures\Factories\ImageFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[UseFactory(ImageFactory::class)]
class Image extends Model
{
    use HasFactory;

    protected $fillable = ['url', 'imageable_id', 'imageable_type'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
