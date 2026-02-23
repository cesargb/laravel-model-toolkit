<div align="center">
    <img alt="Logo for Laravel Model Toolkit" src="art/laravel-model-toolkit.webp" style="max-height: 300px; width: 100%; max-width: 900px; margin-bottom: 20px; object-fit: cover;">
</div>
<div align="left">
<h1>Laravel Model Toolkit</h1>

[![tests](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/tests.yml/badge.svg)](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/tests.yml)
[![static analysis](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/static-analysis.yml)
[![lint](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/lint.yml/badge.svg)](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/lint.yml)

</div>

A Laravel package that automatically discovers all Eloquent models,
detects orphaned records in polymorphic relationships, and removes
unwanted entries. It also executes cleanup for prunable models in a
single pass.

------------------------------------------------------------------------

## What Does This Package Do?

When you delete a parent model (for example, a `Post`), related tables
using **polymorphic relationships** (such as `images`, `comments`, or
`taggables`) may retain records pointing to IDs that no longer exist.
These are considered **orphaned records**.

This package:

- ✅ Automatically scans all Eloquent models in your application
- ✅ Detects and removes orphaned records in polymorphic relationships
- ✅ Detects and executes cleanup for models using Laravel's `Prunable`
    or `MassPrunable` traits in the same command

![Laravel Model Toolkit](/art/laravel-model-toolkit.webp){: style="max-width: 500px" }

------------------------------------------------------------------------

## Requirements

- PHP 8.4 or higher
- Laravel 12 or higher

------------------------------------------------------------------------

## Installation

Install the package via Composer:

``` bash
composer require cesargb/laravel-model-toolkit
```

------------------------------------------------------------------------

## Available Commands

### model:clean

Displays orphaned records and prunable models, then removes them.

``` bash
php artisan model:clean
```

#### Options

--pretend; Preview what would be deleted without actually deleting records
--chunk=1000; Number of records processed per batch during deletion

------------------------------------------------------------------------

### model:list

Lists all discovered Eloquent models grouped by namespace along with
their database tables.

``` bash
php artisan model:list
```

#### Options

--json;   Output results in JSON format

------------------------------------------------------------------------

## Programmatic Usage

You can also use the internal classes directly in your application code.

### Retrieve All Polymorphic Relationships and Count Orphans

``` php
use Cesargb\ModelToolkit\Morph;

$morph = new Morph();
$models = $morph->get();
```

------------------------------------------------------------------------

### Clean Orphans for a Specific Model and Relation

``` php
$deleted = $morph->clean(\App\Models\Post::class, 'comments');
```

------------------------------------------------------------------------

### List Prunable Models

``` php
use Cesargb\ModelToolkit\Prunable;

$prunable = new Prunable();
$models = $prunable->get();
```

This is useful if you want to integrate cleanup logic directly into your
application instead of relying solely on Artisan commands.

------------------------------------------------------------------------

## License

This project is licensed under the MIT License, meaning you are free to
use, modify, and distribute it.
