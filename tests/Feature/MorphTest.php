<?php

namespace Cesargb\ModelToolkit\Tests\Feature;

use Cesargb\ModelToolkit\Morph;
use Cesargb\ModelToolkit\Tests\Fixtures\Models\Comment;
use Cesargb\ModelToolkit\Tests\Fixtures\Models\Post;
use Cesargb\ModelToolkit\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class MorphTest extends TestCase
{
    use RefreshDatabase;

    private string $discoveryAppPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discoveryAppPath = realpath(__DIR__.'/../Fixtures/DiscoveryApp');
    }

    public function test_clean_returns_a_successful_result_with_the_deleted_count(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);
        DB::table('comments')->insert([
            'commentable_type' => Post::class,
            'commentable_id' => 99999,
            'body' => 'orphaned comment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = (new Morph($this->discoveryAppPath))->clean(Post::class, 'comments');

        $this->assertTrue($result->succeeded());
        $this->assertSame(1, $result->deletedCount());
        $this->assertSame(1, DB::table('comments')->count());
    }

    public function test_clean_returns_a_failed_result_for_an_unknown_model(): void
    {
        $result = (new Morph($this->discoveryAppPath))->clean('App\\Models\\DoesNotExist', 'comments');

        $this->assertTrue($result->failed());
        $this->assertNotNull($result->error());
    }

    public function test_clean_returns_a_failed_result_for_an_unknown_method(): void
    {
        $result = (new Morph($this->discoveryAppPath))->clean(Post::class, 'doesNotExist');

        $this->assertTrue($result->failed());
        $this->assertNotNull($result->error());
    }
}
