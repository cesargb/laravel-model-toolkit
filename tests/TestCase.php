<?php

namespace Cesargb\MorphCleaner\Tests;

use Cesargb\MorphCleaner\MorphCleanerServiceProvider;
use Cesargb\MorphCleaner\Tests\Fixtures\FixturesServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            MorphCleanerServiceProvider::class,
            FixturesServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
