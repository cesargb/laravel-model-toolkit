<?php

namespace Cesargb\ModelToolkit\Filters;

use Cesargb\ModelToolkit\Filters\Contracts\Filterable;

class LaravelFilter implements Filterable
{
    public function __construct(private string $appPath) {}

    public function apply(array $classes): array
    {
        $laravelNamespaces = $this->namespacesOfLaravelPackages();

        if (is_null($laravelNamespaces)) {
            return $classes;
        }

        $namespaces = array_merge($laravelNamespaces, $this->namespacesOfRoot($classes));

        return array_filter($classes, function ($filename, $className) use ($namespaces) {
            foreach ($namespaces as $namespaceProvider) {
                if (str_starts_with($className, $namespaceProvider)) {
                    return true;
                }
            }

            return false;

        }, ARRAY_FILTER_USE_BOTH);
    }

    private function namespacesOfLaravelPackages(): ?array
    {
        if (! file_exists($this->appPath.'/bootstrap/cache/packages.php')) {
            return null;
        }

        $manifest = $this->appPath === base_path()
            ? app(\Illuminate\Foundation\PackageManifest::class)
            : new \Illuminate\Foundation\PackageManifest(
                files: new \Illuminate\Filesystem\Filesystem,
                basePath: $this->appPath,
                manifestPath: $this->appPath.'/bootstrap/cache/packages.php'
            );

        $providers = $manifest->providers();

        $namespaceProviders = array_map(function ($provider) {
            return substr($provider, 0, strrpos($provider, '\\'));
        }, $providers);

        return $namespaceProviders;
    }

    private function namespacesOfRoot(array $classes): array
    {
        $composerJsonFilename = realpath($this->appPath.'/composer.json');

        $composerJson = json_decode(file_get_contents($composerJsonFilename), true);

        return array_keys($composerJson['autoload']['psr-4'] ?? []);
    }
}
