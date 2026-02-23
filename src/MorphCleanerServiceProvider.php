<?php

namespace Cesargb\MorphCleaner;

use Cesargb\MorphCleaner\Console\Commands\ModelCleanCommand;
use Cesargb\MorphCleaner\Console\Commands\ModelListCommand;
use Illuminate\Support\ServiceProvider;

class MorphCleanerServiceProvider extends ServiceProvider
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
