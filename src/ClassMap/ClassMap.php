<?php

namespace Cesargb\MorphCleaner\ClassMap;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Foundation\Auth\User;

class ClassMap
{
    private bool $dev = false;

    private array $classMapped = [];

    public function __construct(
        private string $appPath,
    ) {}

    public function dev(bool $dev = true): self
    {
        $this->dev = $dev;

        return $this;
    }

    public function build(): self
    {
        $maps = array_merge(
            $this->inBase(),
            $this->inVendor(),
        );

        $this->classMapped = array_map(function ($filename, $fqcn) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $fileContent = file_get_contents($filename);

            $type = match (true) {
                preg_match('/\btrait\s+'.$name.'\b/', $fileContent) === 1 => TypeDeclaration::TRAIT,
                preg_match('/\binterface\s+'.$name.'\b/', $fileContent) === 1 => TypeDeclaration::INTERFACE,
                preg_match('/\babstract\s+class\s+'.$name.'\b/', $fileContent) === 1 => TypeDeclaration::ABSTRACT_CLASS,
                preg_match('/\benum\s+'.$name.'\b/', $fileContent) === 1 => TypeDeclaration::ENUM,
                preg_match('/\bclass\s+'.$name.'\b/', $fileContent) === 1 => TypeDeclaration::_CLASS,
                default => TypeDeclaration::UNKNOWN,
            };

            $extendedOf = $this->extendedOf($fileContent);

            return [
                'filename' => $filename,
                'fqcn' => $fqcn,
                'namespace' => str_replace('\\'.$name, '', $fqcn),
                'name' => $name,
                'type' => $type,
                'extend' => $extendedOf,
            ];
        }, $maps, array_keys($maps));

        return $this;
    }

    public function models(): array
    {
        $abstractsExtendModel = array_filter(
            $this->abstracts(),
            fn ($item) => $item['extend'] === Model::class
        );

        $modelExtendedOf = [
            Model::class,
            ...array_column($abstractsExtendModel, 'fqcn'),
            User::class,
        ];

        return array_filter(
            $this->classes(),
            function ($item) use ($modelExtendedOf) {
                $extendModel = $item['extend'] ?? null;

                if (in_array($extendModel, $modelExtendedOf)) {
                    $reflection = new \ReflectionClass($item['fqcn']);

                    return $reflection->isSubclassOf(Model::class);
                }

                return false;
            }
        );
    }

    public function prunable(): array
    {
        return array_filter(
            $this->models(),
            function ($model) {
                $reflection = new \ReflectionClass($model['fqcn']);

                $traits = $reflection->getTraitNames();

                return in_array(Prunable::class, $traits)
                    || in_array(MassPrunable::class, $traits);
            }
        );
    }

    private function abstracts(): array
    {
        if (! $this->classMapped) {
            $this->build();
        }

        return array_filter(
            $this->classMapped,
            fn ($item) => $item['type'] === TypeDeclaration::ABSTRACT_CLASS
        );
    }

    private function classes(): array
    {
        if (! $this->classMapped) {
            $this->build();
        }

        return array_filter(
            $this->classMapped,
            fn ($item) => $item['type'] === TypeDeclaration::_CLASS
        );
    }

    private function inBase(): array
    {
        return (new ClassMapInBase($this->appPath.'/composer.json'))->dev($this->dev)->get();
    }

    private function inVendor(): array
    {
        return (new ClassMapInVendor($this->appPath))->get();
    }

    private function extendedOf(string $fileContent): ?string
    {
        $extendedOf = match (true) {
            preg_match('/\bextends\s+([\\\\\w]+)\b/', $fileContent, $matches) === 1 => $matches[1],
            default => null,
        };

        if (! str_contains($extendedOf ?? '\\', '\\')) {
            $extendedOf = match (true) {
                preg_match('/\buse\s+([\\\\\w]+)\s+as\s+'.$extendedOf.'\b/', $fileContent, $matches) === 1 => $matches[1],
                preg_match('/\buse\s+([\\\\\w]+)'.$extendedOf.'\b/', $fileContent, $matches) === 1 => $matches[1].$extendedOf,
                default => $extendedOf,
            };
        }

        return $extendedOf;
    }
}
