<?php

namespace Cesargb\ModelToolkit;

use Cesargb\ModelToolkit\Console\Commands\ModelCleanCommand;
use Cesargb\ModelToolkit\Console\Commands\ModelListCommand;
use Illuminate\Support\ServiceProvider;

class ModelToolkitServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModelListCommand::class,
                ModelCleanCommand::class,
            ]);
        }
    }
}
