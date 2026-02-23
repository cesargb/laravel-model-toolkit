<?php

namespace Cesargb\ModelToolkit\Filters\Contracts;

interface Filterable
{
    public function apply(array $classes): array;
}
