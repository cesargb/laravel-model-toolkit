<?php

namespace Cesargb\MorphCleaner\Filters\Contracts;

interface Filterable
{
    public function apply(array $classes): array;
}
