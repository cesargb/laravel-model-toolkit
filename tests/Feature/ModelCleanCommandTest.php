<?php

namespace Cesargb\MorphCleaner\Tests\Feature;

use Cesargb\MorphCleaner\Tests\Fixtures\Models\Comment;
use Cesargb\MorphCleaner\Tests\Fixtures\Models\Post;
use Cesargb\MorphCleaner\Tests\Fixtures\Models\Tag;
use Cesargb\MorphCleaner\Tests\Fixtures\Models\Video;
use Cesargb\MorphCleaner\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ModelCleanCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $discoveryAppPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discoveryAppPath = realpath(__DIR__.'/../Fixtures/DiscoveryApp');
    }

    public function test_command_exits_successfully(): void
    {
        $this->artisan('model:clean', ['--path' => $this->discoveryAppPath])
            ->assertExitCode(0);
    }

    public function test_shows_all_clean_message_when_no_orphans_exist(): void
    {
        $this->artisan('model:clean', ['--path' => $this->discoveryAppPath])
            ->expectsOutputToContain('All morph relations are clean!')
            ->assertExitCode(0);
    }

    public function test_pretend_shows_morph_many_orphan_count_without_deleting(): void
    {
        $this->insertOrphanedComment();
        $this->insertOrphanedComment();

        Artisan::call('model:clean', [
            '--path' => $this->discoveryAppPath,
            '--pretend' => true,
        ]);

        $this->assertSame(2, DB::table('comments')->count());
        $this->assertStringContainsString('2', Artisan::output());
    }

    public function test_pretend_shows_morph_one_orphan_count_without_deleting(): void
    {
        $this->insertOrphanedImage();

        Artisan::call('model:clean', [
            '--path' => $this->discoveryAppPath,
            '--pretend' => true,
        ]);

        $this->assertSame(1, DB::table('images')->count());
        $this->assertStringContainsString('1', Artisan::output());
    }

    public function test_clean_deletes_orphaned_morph_many_records(): void
    {
        $this->insertOrphanedComment();
        $this->insertOrphanedComment();

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(0, DB::table('comments')->count());
    }

    public function test_clean_deletes_orphaned_morph_one_records(): void
    {
        $this->insertOrphanedImage();

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(0, DB::table('images')->count());
    }

    public function test_clean_deletes_orphaned_morph_to_many_records(): void
    {
        $tag = Tag::factory()->create();
        $this->insertOrphanedTaggable($tag->id);

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(0, DB::table('taggables')->where('taggable_type', Post::class)->count());
    }

    public function test_clean_does_not_delete_records_with_existing_parent(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(1, DB::table('comments')->count());
    }

    public function test_clean_only_deletes_orphaned_and_keeps_valid_records(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);
        $this->insertOrphanedComment();

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(1, DB::table('comments')->count());
    }

    public function test_clean_orphans_from_different_morph_types_independently(): void
    {
        Post::factory()->create();
        $this->insertOrphanedComment(Post::class);
        $this->insertOrphanedImage(Video::class);

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(0, DB::table('comments')
            ->where('commentable_type', Post::class)
            ->count());

        $this->assertSame(0, DB::table('images')
            ->where('imageable_type', Video::class)
            ->count());
    }

    public function test_clean_output_shows_deleted_count(): void
    {
        $this->insertOrphanedComment();
        $this->insertOrphanedComment();
        $this->insertOrphanedComment();

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertStringContainsString('deleted', Artisan::output());
    }

    public function test_pretend_output_shows_model_and_method_name(): void
    {
        $this->insertOrphanedComment();

        Artisan::call('model:clean', [
            '--path' => $this->discoveryAppPath,
            '--pretend' => true,
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('Post', $output);
        $this->assertStringContainsString('comments', $output);
    }

    public function test_clean_morph_many_via_video_parent(): void
    {
        $this->insertOrphanedComment(Video::class);

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(0, DB::table('comments')
            ->where('commentable_type', Video::class)
            ->count());
    }

    private function insertOrphanedComment(?string $morphType = null, int $morphId = 99999): void
    {
        DB::table('comments')->insert([
            'commentable_type' => $morphType ?? Post::class,
            'commentable_id' => $morphId,
            'body' => 'orphaned comment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertOrphanedImage(?string $morphType = null, int $morphId = 99999): void
    {
        DB::table('images')->insert([
            'imageable_type' => $morphType ?? Post::class,
            'imageable_id' => $morphId,
            'url' => 'https://example.com/orphan.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertOrphanedTaggable(int $tagId, ?string $morphType = null, int $morphId = 99999): void
    {
        DB::table('taggables')->insert([
            'tag_id' => $tagId,
            'taggable_type' => $morphType ?? Post::class,
            'taggable_id' => $morphId,
        ]);
    }
}
