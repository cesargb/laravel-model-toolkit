# Laravel Model Toolkit

[![tests](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/tests.yml/badge.svg)](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/tests.yml)
[![static analysis](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/static-analysis.yml)
[![lint](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/lint.yml/badge.svg)](https://github.com/cesargb/laravel-model-toolkit/actions/workflows/lint.yml)

A Laravel package that automatically discovers all Eloquent models in your application, detects **orphaned morph relation records**, and cleans them up. It also handles **prunable models** in the same pass.

## What it does

When you delete a parent model (e.g. `Post`), any polymorphic records pointing to it (e.g. rows in `images`, `comments`, or `taggables`) become orphaned — they reference a `post_id` that no longer exists. This package scans your models, finds those orphaned rows, and deletes them.

It also discovers models that use Laravel's `Prunable` or `MassPrunable` traits and runs them as part of the same cleanup command.

## Requirements

- PHP 8.4+
- Laravel 12+

## Installation

```bash
composer require cesargb/laravel-model-toolkit
```

The service provider is auto-discovered via Laravel's package discovery.

## Commands

### `model:clean`

Shows orphaned morph relation records and prunable models, then cleans them up.

```bash
php artisan model:clean
```

Use `--pretend` to preview what would be deleted without making any changes:

```bash
php artisan model:clean --pretend
```

Available options:

| Option | Description |
|--------|-------------|
| `--pretend` | Show orphan counts without deleting anything |
| `--chunk=1000` | Number of records to process per chunk when deleting |

### `model:list`

Lists all discovered Eloquent models grouped by namespace, with their associated database table.

```bash
php artisan model:list
```

| Option | Description |
|--------|-------------|
| `--json` | Output results as JSON |

## Programmatic usage

You can use the core classes directly:

```php
use Cesargb\MorphCleaner\Morph;
use Cesargb\MorphCleaner\Prunable;

// Get all models with morph relations and their orphan counts
$morph = new Morph();
$models = $morph->get();

// Clean orphaned records for a specific model and relation method
$deleted = $morph->clean(\App\Models\Post::class, 'comments');

// Get all models that use Prunable or MassPrunable traits
$prunable = new Prunable();
$models = $prunable->get();
```

## License

MIT
