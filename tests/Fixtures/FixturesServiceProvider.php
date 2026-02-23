<?php

namespace Cesargb\ModelToolkit\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

class FixturesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');
    }
}
