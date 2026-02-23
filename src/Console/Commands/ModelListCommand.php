<?php

namespace Cesargb\MorphCleaner\Console\Commands;

use Cesargb\MorphCleaner\ClassMap\ClassMap;
use Illuminate\Console\Command;

class ModelListCommand extends Command
{
    protected $signature = 'model:list
                            {--dev : Include models from the "app/Models" directory only}
                            {--path= : Application base path for model discovery}
                            {--json : Output in JSON format}';

    protected $description = 'List all models in the application';

    public function handle(): int
    {
        $appPath = $this->option('path') ?: base_path();

        $models = (new ClassMap($appPath))->generate()->models();

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

            if ($metadata['namespace'] !== $currentNamespace) {
                $currentNamespace = $metadata['namespace'];
                $this->newLine();
                $this->components->twoColumnDetail("<fg=green>{$currentNamespace}</>", '');
            }
            $class = new $metadata['fqcn'];

            $table = $class->getTable();

            $this->components->twoColumnDetail("<options=bold>{$metadata['name']}</>", "<fg=gray>{$table}</>");
        }

        $this->newLine();
    }
}
