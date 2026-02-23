<?php

namespace Cesargb\MorphCleaner\ClassMap;

use Symfony\Component\Finder\Finder;

final class ClassMapInBase
{
    private bool $dev = false;

    private string $pasePath;

    public function __construct(
        private string $composerJsonFilename,
    ) {
        $this->pasePath = dirname($composerJsonFilename);
    }

    public function dev(bool $dev = true): self
    {
        $this->dev = $dev;

        return $this;
    }

    public function get(): array
    {
        $psr4 = $this->psr4();

        $classes = [];

        foreach ($psr4 as $namespace => $path) {
            $classes = array_merge($classes, $this->searchFiles($namespace, $path));
        }

        return $classes;
    }

    private function searchFiles(string $namespace, string $path): array
    {
        if (! is_dir($this->pasePath.'/'.$path)) {
            return [];
        }

        $files = new Finder()->files()
            ->in($this->pasePath.'/'.$path)
            ->name('*.php');

        $classes = [];

        foreach ($files as $file) {
            $relativePath = $file->getPath().'/'.$file->getFilename();
            $namespaceRelative = str_replace('/', '\\', $file->getRelativePath());
            if (! empty($namespaceRelative)) {
                $namespaceRelative = $namespaceRelative.'\\';
            }
            $className = $file->getFilenameWithoutExtension();

            $fqcn = $namespace.$namespaceRelative.$className;
            $classes[$fqcn] = $relativePath;
        }

        return $classes;
    }

    private function psr4(): array
    {
        $composerJson = json_decode(file_get_contents($this->composerJsonFilename), true);

        $classesInApp = $composerJson['autoload']['psr-4'] ?? [];

        if ($this->dev) {
            $classesInApp = array_merge($classesInApp, $composerJson['autoload-dev']['psr-4'] ?? []);
        }

        return $classesInApp;
    }
}
