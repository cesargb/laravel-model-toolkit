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
        $this->newLine();
        $this->info('Models orphaned morph relations:');
        $this->newLine();

        $modelsToReport = array_filter($modelsWithMorphs, fn ($model) => $this->hasOrphansOrErrors($model));

        if (! $modelsToReport) {
            $this->components->info('All morph relations are clean.');
            $this->newLine();

            return;
        }

        foreach ($modelsToReport as $model) {
            $metadata = $model['metadata'];

            foreach ($model['methods'] as $method) {
                $label = "<options=bold>{$metadata['fqcn']}::{$method['name']}</>";

                if (isset($method['count']['error'])) {
                    $this->components->twoColumnDetail(
                        $label,
                        "<fg=red;options=bold>error: {$method['count']['error']}</>"
                    );

                    continue;
                }

                $numOrphans = $method['count']['orphans'];

                if ($numOrphans === 0) {
                    continue;
                }

                if ($this->option('pretend')) {
                    $this->components->twoColumnDetail($label, "<fg=yellow;options=bold>{$numOrphans}</>");

                    continue;
                }

                $result = $this->morph->clean($metadata['fqcn'], $method['name']);

                if ($result->failed()) {
                    $this->components->twoColumnDetail($label, "<fg=red;options=bold>failed: {$result->error()}</>");

                    continue;
                }

                $this->components->twoColumnDetail($label, "<fg=red;options=bold>{$result->deletedCount()} deleted</>");
            }
        }

        $this->newLine();
    }

    private function hasOrphansOrErrors(array $model): bool
    {
        foreach ($model['methods'] as $method) {
            if (isset($method['count']['error']) || $method['count']['orphans'] > 0) {
                return true;
            }
        }

        return false;
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
