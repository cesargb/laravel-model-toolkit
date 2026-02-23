<?php

namespace Cesargb\MorphCleaner\Tests\Fixtures\Models;

use Cesargb\MorphCleaner\Tests\Fixtures\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[UseFactory(CommentFactory::class)]
class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['body', 'commentable_id', 'commentable_type'];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
