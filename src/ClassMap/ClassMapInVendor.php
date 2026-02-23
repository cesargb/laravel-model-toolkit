<?php

namespace Cesargb\MorphCleaner\ClassMap;

use Cesargb\MorphCleaner\Filters\LaravelFilter;

final class ClassMapInVendor
{
    private string $vendorPath;

    private string $composerClassmapFilename;

    public function __construct(
        private string $appPath,
    ) {
        $this->vendorPath = realpath($appPath.'/vendor');
        $this->composerClassmapFilename = $this->vendorPath.'/composer/autoload_classmap.php';

        if (! file_exists($this->composerClassmapFilename)) {
            throw new \RuntimeException('The autoload_classmap.php file does not exist. Make sure to run composer dump-autoload.');
        }
    }

    public function get(): array
    {
        $classesInVendor = require $this->composerClassmapFilename;
        $classesInVendor = new LaravelFilter($this->appPath)->apply($classesInVendor);

        return $classesInVendor;
    }
}
