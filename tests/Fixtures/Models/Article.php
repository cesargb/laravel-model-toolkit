<?php

namespace Cesargb\ModelToolkit\Tests\Fixtures\Models;

use Cesargb\ModelToolkit\Tests\Fixtures\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[UseFactory(ArticleFactory::class)]
class Article extends Model
{
    use HasFactory;

    protected $fillable = ['legacy_id', 'title'];

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable', null, null, 'legacy_id');
    }
}
