<?php

namespace Cesargb\MorphCleaner;

use Cesargb\MorphCleaner\ClassMap\ClassMap;

class Prunable
{
    private bool $dev = false;

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
        $models = new ClassMap($this->appPath)->dev($this->dev)->build()->prunable();

        return $models;
    }
}
