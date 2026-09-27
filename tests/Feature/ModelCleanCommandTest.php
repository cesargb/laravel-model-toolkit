<?php

namespace Cesargb\ModelToolkit\Tests\Feature;

use Cesargb\ModelToolkit\Morph;
use Cesargb\ModelToolkit\Tests\Fixtures\CrossConnectionModels\Podcast;
use Cesargb\ModelToolkit\Tests\Fixtures\Models\Article;
use Cesargb\ModelToolkit\Tests\Fixtures\Models\Comment;
use Cesargb\ModelToolkit\Tests\Fixtures\Models\Post;
use Cesargb\ModelToolkit\Tests\Fixtures\Models\Tag;
use Cesargb\ModelToolkit\Tests\Fixtures\Models\Video;
use Cesargb\ModelToolkit\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModelCleanCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $discoveryAppPath;

    private string $crossConnectionAppPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discoveryAppPath = realpath(__DIR__.'/../Fixtures/DiscoveryApp');
        $this->crossConnectionAppPath = realpath(__DIR__.'/../Fixtures/CrossConnectionApp');
    }

    public function test_command_exits_successfully(): void
    {
        $this->artisan('model:clean', ['--path' => $this->discoveryAppPath])
            ->assertExitCode(0);
    }

    public function test_shows_all_clean_message_when_no_orphans_exist(): void
    {
        $this->artisan('model:clean', ['--path' => $this->discoveryAppPath])
            ->expectsOutputToContain('All morph relations are clean.')
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

    public function test_clean_does_not_delete_valid_self_referencing_replies(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
        ]);
        Comment::factory()->create([
            'commentable_type' => Comment::class,
            'commentable_id' => $comment->id,
        ]);
        $this->insertOrphanedComment(Comment::class);

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(2, DB::table('comments')->count());
        $this->assertSame(0, DB::table('comments')
            ->where('commentable_type', Comment::class)
            ->where('commentable_id', 99999)
            ->count());
    }

    public function test_clean_uses_relation_local_key(): void
    {
        $article = Article::factory()->create(['legacy_id' => 500]);
        Comment::factory()->create([
            'commentable_type' => Article::class,
            'commentable_id' => 500,
        ]);
        $this->insertOrphanedComment(Article::class);

        Artisan::call('model:clean', ['--path' => $this->discoveryAppPath]);

        $this->assertSame(1, DB::table('comments')
            ->where('commentable_type', Article::class)
            ->count());
        $this->assertSame(500, DB::table('comments')
            ->where('commentable_type', Article::class)
            ->value('commentable_id'));
        $this->assertSame(500, $article->legacy_id);
    }

    public function test_clean_skips_relations_across_connections(): void
    {
        Schema::connection('secondary')->create('podcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
        Schema::connection('secondary')->create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->text('body');
        });
        DB::connection('secondary')->table('podcasts')->insert(['title' => 'decoy podcast']);
        DB::connection('secondary')->table('comments')->insert([
            'commentable_type' => Podcast::class,
            'commentable_id' => 99999,
            'body' => 'decoy comment in the parent connection',
        ]);

        $this->insertOrphanedComment(Podcast::class);

        Artisan::call('model:clean', ['--path' => $this->crossConnectionAppPath]);

        $this->assertSame(1, DB::connection('secondary')->table('comments')->count());
        $this->assertSame(1, DB::table('comments')
            ->where('commentable_type', Podcast::class)
            ->count());
        $this->assertStringContainsString('error:', Artisan::output());

        $models = (new Morph($this->crossConnectionAppPath))->get();
        $podcast = array_find($models, fn ($model) => $model['fqcn'] === Podcast::class);
        $method = array_find($podcast['methods'], fn ($m) => $m['name'] === 'comments');

        $this->assertArrayHasKey('error', $method['count']);
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
