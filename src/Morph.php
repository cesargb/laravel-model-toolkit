<?php

namespace Cesargb\ModelToolkit;

use Cesargb\ModelToolkit\ClassMap\ClassMap;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Morph
{
    private bool $dev = false;

    private array $cacheModelsMapped = [];

    public function __construct(
        private string $appPath = '',
    ) {
        $this->appPath = $appPath ?: base_path();
    }

    public function dev(bool $dev = true): self
    {
        $this->dev = $dev;

        return $this;
    }

    public function get(): array
    {
        if (! empty($this->cacheModelsMapped)) {
            return $this->cacheModelsMapped;
        }

        $models = new ClassMap($this->appPath)->dev($this->dev)->build()->models();

        $modelsMapped = $this->getModelsWithMorphRelations($models);

        $this->cacheModelsMapped = array_map(
            fn ($model) => $this->processModel($model),
            $modelsMapped
        );

        return $this->cacheModelsMapped;
    }

    public function clean(string $modelFqcn, string $method): ?int
    {
        $mappedModels = $this->get();
        $model = array_find($mappedModels, fn ($model) => $model['fqcn'] === $modelFqcn);
        $methodData = array_find($model['methods'], fn ($m) => $m['name'] === $method);

        return $this->cleanMethod($modelFqcn, $methodData);
    }

    private function getModelsWithMorphRelations(array $models): array
    {
        unset($models[self::class]);

        $this->autoload();

        $classesMapped = array_map(
            fn ($model) => $this->mapClasses($model),
            $models
        );

        return array_filter($classesMapped);
    }

    private function mapClasses(array $metadata): array
    {
        $reflection = new \ReflectionClass($metadata['fqcn']);

        $methodsReturnMorph = array_filter(
            $reflection->getMethods(),
            function ($method) {
                $returnType = $method->getReturnType();
                if (is_null($returnType) || ! method_exists($returnType, 'getName')) {
                    return false;
                }

                return in_array($returnType->getName() ?? '', [
                    MorphOne::class,
                    MorphMany::class,
                    MorphToMany::class,
                ]);
            }
        );

        if (empty($methodsReturnMorph)) {
            return [];
        }

        $class = new $metadata['fqcn'];

        return [
            'metadata' => $metadata,
            'fqcn' => $metadata['fqcn'],
            'methods' => array_map(
                function ($method) use ($class) {
                    $relation = $method->invoke($class);

                    $fieldType = $relation->getMorphType();

                    if ($relation instanceof MorphOneOrMany) {
                        $table = $relation->getRelated()->getTable();
                        $fieldId = $relation->getForeignKeyName();
                    } elseif ($relation instanceof MorphToMany) {
                        $table = $relation->getTable();
                        $fieldId = $relation->getForeignPivotKeyName();
                    }
                    $returnType = $method->getReturnType();

                    $returnTypeName = match (true) {
                        $returnType instanceof \ReflectionNamedType => $returnType->getName(),
                        default => null,
                    };

                    return [
                        'name' => $method->getName(),
                        'return_type' => $returnTypeName,
                        'morph_model' => [
                            'connection' => $relation->getConnection()->getConfig('name'),
                            'type_relation' => $relation instanceof MorphToMany ? MorphToMany::class : MorphOneOrMany::class,
                            'table' => $table ?? null,
                            'fields' => [
                                'id' => $fieldId ?? null,
                                'type' => $fieldType ?? null,
                            ],
                        ],
                    ];
                },
                $methodsReturnMorph
            ),
        ];
    }

    private function autoload(): void
    {
        if (! $this->dev) {
            return;
        }

        if ($this->appPath !== base_path()) {
            require $this->appPath.'/vendor/autoload.php';
        }
    }

    private function processModel(array $model): array
    {
        $model['methods'] = array_map(
            fn ($method) => $this->processMethod($model['fqcn'], $method),
            $model['methods']
        );

        return $model;
    }

    private function processMethod(string $parentFqcn, array $method): array
    {
        try {
            $residualCount = $this->builderCleaner($parentFqcn, $method)->count();
            $method['count']['orphans'] = $residualCount;
        } catch (\Throwable $th) {
            $error = $th->getMessage();
            $method['count']['error'] = $error;
        }

        return $method;
    }

    public function cleanMethod(string $parentFqcn, array $method): ?int
    {
        try {
            return $this->builderCleaner($parentFqcn, $method)->delete();
        } catch (\Throwable $th) {
            return null;
        }
    }

    private function builderCleaner(string $parentFqcn, array $method): Builder
    {
        $morphModelTable = $method['morph_model']['table'];
        $morphFieldType = $method['morph_model']['fields']['type'];
        $morphFieldId = $method['morph_model']['fields']['id'];
        $parentModel = new $parentFqcn;

        return DB::connection($parentModel->getConnectionName())
            ->table($morphModelTable)
            ->where($morphFieldType, $parentModel->getMorphClass())
            ->whereNotExists(function ($query) use (
                $parentModel,
                $morphModelTable,
                $morphFieldId
            ) {
                $query->select(DB::raw(1))
                    ->from($parentModel->getTable())
                    ->whereColumn($parentModel->getTable().'.'.$parentModel->getKeyName(), '=', $morphModelTable.'.'.$morphFieldId);
            });
    }
}
