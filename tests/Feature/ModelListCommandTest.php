<?php

namespace Cesargb\ModelToolkit\Tests\Feature;

use Cesargb\ModelToolkit\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class ModelListCommandTest extends TestCase
{
    private string $discoveryAppPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discoveryAppPath = realpath(__DIR__.'/../Fixtures/DiscoveryApp');
    }

    public function test_command_exits_successfully(): void
    {
        $this->artisan('model:list', [
            '--path' => $this->discoveryAppPath,
        ])->assertExitCode(0);
    }

    public function test_dev_flag_is_accepted(): void
    {
        $this->artisan('model:list', [
            '--dev' => true,
            '--path' => $this->discoveryAppPath,
        ])->assertExitCode(0);
    }

    public function test_json_flag_outputs_valid_json(): void
    {
        Artisan::call('model:list', [
            '--json' => true,
            '--path' => $this->discoveryAppPath,
        ]);

        $this->assertJson(Artisan::output());
    }

    public function test_json_output_is_an_array(): void
    {
        Artisan::call('model:list', [
            '--json' => true,
            '--path' => $this->discoveryAppPath,
        ]);

        $models = json_decode(Artisan::output(), true);

        $this->assertIsArray($models);
    }

    public function test_json_output_contains_all_fixture_models(): void
    {
        Artisan::call('model:list', [
            '--json' => true,
            '--path' => $this->discoveryAppPath,
        ]);

        $models = json_decode(Artisan::output(), true);
        $fqcns = array_column($models, 'fqcn');

        $this->assertContains('Cesargb\ModelToolkit\Tests\Fixtures\Models\Post', $fqcns);
        $this->assertContains('Cesargb\ModelToolkit\Tests\Fixtures\Models\Video', $fqcns);
        $this->assertContains('Cesargb\ModelToolkit\Tests\Fixtures\Models\Image', $fqcns);
        $this->assertContains('Cesargb\ModelToolkit\Tests\Fixtures\Models\Comment', $fqcns);
        $this->assertContains('Cesargb\ModelToolkit\Tests\Fixtures\Models\Tag', $fqcns);
    }

    public function test_json_models_have_expected_keys(): void
    {
        Artisan::call('model:list', [
            '--json' => true,
            '--path' => $this->discoveryAppPath,
        ]);

        $models = json_decode(Artisan::output(), true);

        $this->assertNotEmpty($models);

        foreach ($models as $model) {
            $this->assertArrayHasKey('fqcn', $model);
            $this->assertArrayHasKey('name', $model);
            $this->assertArrayHasKey('namespace', $model);
            $this->assertArrayHasKey('type', $model);
            $this->assertArrayHasKey('filename', $model);
            $this->assertArrayHasKey('extend', $model);
        }
    }

    public function test_json_model_name_is_short_class_name(): void
    {
        Artisan::call('model:list', [
            '--json' => true,
            '--path' => $this->discoveryAppPath,
        ]);

        $models = json_decode(Artisan::output(), true);
        $modelsByFqcn = array_column($models, null, 'fqcn');

        $post = $modelsByFqcn['Cesargb\ModelToolkit\Tests\Fixtures\Models\Post'];

        $this->assertSame('Post', $post['name']);
        $this->assertSame('Cesargb\ModelToolkit\Tests\Fixtures\Models', $post['namespace']);
        $this->assertSame('class', $post['type']);
        $this->assertSame(Model::class, $post['extend']);
    }

    public function test_json_filename_points_to_existing_php_file(): void
    {
        Artisan::call('model:list', [
            '--json' => true,
            '--path' => $this->discoveryAppPath,
        ]);

        $models = json_decode(Artisan::output(), true);

        foreach ($models as $model) {
            $this->assertFileExists($model['filename']);
            $this->assertStringEndsWith('.php', $model['filename']);
        }
    }

    public function test_json_type_field_is_class_for_all_models(): void
    {
        Artisan::call('model:list', [
            '--json' => true,
            '--path' => $this->discoveryAppPath,
        ]);

        $models = json_decode(Artisan::output(), true);

        foreach ($models as $model) {
            $this->assertSame('class', $model['type']);
        }
    }

    public function test_cli_output_shows_model_name_and_table(): void
    {
        Artisan::call('model:list', ['--path' => $this->discoveryAppPath]);
        $output = Artisan::output();

        $this->assertStringContainsString('Post', $output);
        $this->assertStringContainsString('posts', $output);
    }

    public function test_cli_output_groups_models_by_namespace(): void
    {
        Artisan::call('model:list', ['--path' => $this->discoveryAppPath]);
        $output = Artisan::output();

        $this->assertStringContainsString('Cesargb\ModelToolkit\Tests\Fixtures\Models', $output);
    }
}
