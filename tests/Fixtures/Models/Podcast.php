<?php

namespace Cesargb\ModelToolkit\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Podcast extends Model
{
    protected $connection = 'secondary';

    protected $fillable = ['title'];

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
