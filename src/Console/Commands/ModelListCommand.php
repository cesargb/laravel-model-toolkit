<?php

namespace Cesargb\ModelToolkit\Console\Commands;

use Cesargb\ModelToolkit\ClassMap\ClassMap;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelListCommand extends Command
{
    protected $signature = 'model:list
                            {--full : Include models from the entire codebase, including vendor packages}
                            {--dev : Include models from the "app/Models" directory only}
                            {--path= : Application base path for model discovery}
                            {--json : Output in JSON format}';

    protected $description = 'List all models in the application';

    public function handle(): int
    {
        $appPath = $this->option('path') ?: base_path();

        $models = (new ClassMap($appPath))->build()->models();

        $this->option('json') ? $this->displayJson($models) : $this->displayCli($models);

        return self::SUCCESS;
    }

    private function displayJson(array $models): void
    {
        $this->output->writeln(json_encode($models));
    }

    private function displayCli(array $models): void
    {
        $currentNamespace = null;
        foreach ($models as $metadata) {

            if ($metadata['namespace'] !== $currentNamespace && ! $this->getOutput()->isVerbose()) {
                $currentNamespace = $metadata['namespace'];
                $this->newLine();
                $this->components->twoColumnDetail("<fg=green>{$currentNamespace}</>", '');
            }

            $title = $this->getOutput()->isVerbose()
                ? "<fg=green;options=bold>{$metadata['fqcn']}</>"
                : "<options=bold>{$metadata['name']}</>";

            $titleValue = $this->getOutput()->isVerbose() ? '' : "<fg=gray>{$metadata['table']}</>";

            $this->components->twoColumnDetail($title, $titleValue);

            $this->displayCliVerbose($metadata);
        }

        $this->newLine();
    }

    private function displayCliVerbose(array $metadata): void
    {
        if (! $this->getOutput()->isVerbose()) {
            return;
        }

        $model = new $metadata['fqcn'];

        $connection = $model->getConnectionName() ?: 'default';
        $records = $model->count();

        $this->components->twoColumnDetail('connection', $connection);
        $this->components->twoColumnDetail('table', "{$model->getTable()}");
        $this->components->twoColumnDetail('primary key', "{$model->getKeyName()}<fg=gray> ({$model->getKeyType()})</>");
        $this->components->twoColumnDetail('records', $records);

        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model));
        if ($usesSoftDeletes) {
            $recordsWithTrashed = $model->withTrashed()->count();
            $this->components->twoColumnDetail('soft deletable', "records with trashed: <options=bold;fg=yellow>{$recordsWithTrashed}</> <fg=gray>/</> <fg=green>✓</>");
        }

        $isMassivePrunable = in_array(MassPrunable::class, class_uses_recursive($model));
        $isPrunable = $isMassivePrunable || in_array(Prunable::class, class_uses_recursive($model));

        if ($isPrunable) {
            $prunableCount = $model->prunable()->count();

            $this->components->twoColumnDetail('prunable', "prunable records: <options=bold;fg=yellow>{$prunableCount}</> <fg=gray>/</> <fg=green>✓</>");
        }

        $this->newLine();
    }
}
