<?php

namespace Cesargb\MorphCleaner\ClassMap;

enum TypeDeclaration: string
{
    case TRAIT = 'trait';
    case INTERFACE = 'interface';
    case ABSTRACT_CLASS = 'abstract class';
    case _CLASS = 'class';
    case ENUM = 'enum';
    case UNKNOWN = 'unknown';
}
