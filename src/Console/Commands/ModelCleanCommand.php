<?php

namespace Cesargb\ModelToolkit\Console\Commands;

use Cesargb\ModelToolkit\Morph;
use Cesargb\ModelToolkit\Prunable;
use Illuminate\Console\Command;

class ModelCleanCommand extends Command
{
    private Morph $morph;

    protected $signature = 'model:clean
                            {--pretend : Show what would be deleted without actually deleting}
                            {--chunk=1000 : Number of records to process in each chunk when deleting}
                            {--dev : Only for testing - Include models from autoload-dev}
                            {--path= : Application base path for model discovery}';

    protected $description = 'Show or clean up orphaned morph relations or prunable models for all models in the application';

    public function handle(): int
    {
        $appPath = $this->option('path') ?: base_path();

        $this->morph = (new Morph($appPath))->dev($this->option('dev'));
        $modelsWithMorphs = $this->morph->get();
        $modelsPrunable = (new Prunable($appPath))->dev($this->option('dev'))->get();

        $this->displayCli($modelsWithMorphs, $modelsPrunable);

        return self::SUCCESS;
    }

    private function displayCli(array $modelsWithMorphs, array $modelsPrunable): void
    {
        $this->displayCliMorph($modelsWithMorphs);

        $this->displayCliPrunable($modelsPrunable);
    }

    private function displayCliMorph(array $modelsWithMorphs): void
    {
        $this->info('Models orphaned morph relations:');
        $this->newLine();

        $modelsWithHasOrphanedMorphs = array_filter($modelsWithMorphs, function ($model) {
            foreach ($model['methods'] as $method) {
                if (! isset($method['count']['error']) && $method['count']['orphans'] > 0) {
                    return true;
                }
            }

            return false;
        });

        if (! $modelsWithHasOrphanedMorphs) {
            $this->components->info('All morph relations are clean.');
            $this->newLine();

            return;
        }
        $this->info('<fg=green>Models with Orphaned Morph Relations:</>');
        $this->newLine();

        foreach ($modelsWithHasOrphanedMorphs as $model) {
            $metadata = $model['metadata'];

            foreach ($model['methods'] as $method) {
                $numOrphans = $method['count']['orphans'] ?? 0;
                $hasError = isset($method['count']['error']);

                if ($numOrphans === 0 && ! $hasError) {
                    continue;
                }

                if ($this->option('pretend') || $hasError) {
                    $this->components->twoColumnDetail(
                        "<options=bold>{$metadata['fqcn']}::{$method['name']}</>",
                        "<fg=yellow;options=bold>{$numOrphans}</>"
                    );

                    continue;
                }

                $cleaned = $this->morph->clean($metadata['fqcn'], $method['name']);

                $this->components->twoColumnDetail(
                    "<options=bold>{$metadata['fqcn']}::{$method['name']}</>",
                    "<fg=red;options=bold>{$cleaned} deleted</>"
                );
            }
        }

        $this->newLine();
    }

    private function displayCliPrunable(array $modelsPrunable): void
    {
        $this->info('Models prunable:');
        $this->newLine();

        $this->call('model:prune', [
            '--model' => array_column($modelsPrunable, 'fqcn'),
            '--pretend' => $this->option('pretend'),
            '--chunk' => $this->option('chunk'),
        ]);

        $this->newLine();
    }
}
