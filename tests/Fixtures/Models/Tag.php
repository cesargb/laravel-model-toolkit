<?php

namespace Cesargb\MorphCleaner\Tests\Fixtures\Models;

use Cesargb\MorphCleaner\Tests\Fixtures\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[UseFactory(TagFactory::class)]
class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function videos(): MorphToMany
    {
        return $this->morphedByMany(Video::class, 'taggable');
    }
}
