<?php

declare(strict_types=1);

namespace Modules\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;

/**
 * Central service responsible for discovering, caching and querying modules.
 */
/**
 * Central service responsible for discovering, caching, and querying modules.
 * Handles registry, manifest, provider registration, dependency validation, and cache management.
 */
class ModuleManager
{
    /**
     * Check if a provider is registered (for hot reload testing).
     */
    public function isProviderRegistered(string $provider): bool
    {
        return isset($this->registeredProviders[$provider]);
    }

    /**
     * Unregister a module's service provider at runtime (hot reload).
     */
    public function unregisterProvider(string $name): void
    {
        $manifest = $this->manifest($name);
        $provider = $manifest['provider'] ?? null;
        if ($provider && isset($this->registeredProviders[$provider])) {
            // Remove provider from the container (Laravel does not natively support unregister, so we mark as unregistered)
            unset($this->registeredProviders[$provider]);
            // Optionally, clear related bindings, aliases, events, etc. (custom logic can be added here)
        }
    }

    /**
     * Validate dependency version constraints for all enabled modules.
     * Throws InvalidArgumentException if any constraint is not satisfied.
     */
    public function validateDependencyVersions(): void
    {
        foreach ($this->enabledModules() as $name) {
            $manifest = new ModuleManifest($this->path($name));
            $depVersions = $manifest->dependencyVersions();
            foreach ($depVersions as $dep => $constraint) {
                if (! $this->enabled($dep)) {
                    continue; // dependency not enabled, skip
                }
                $depVersion = $this->version($dep);
                if (! \Modules\Support\Semver::satisfies($depVersion, $constraint)) {
                    throw new InvalidArgumentException("Module [{$name}] requires {$dep} version {$constraint}, found {$depVersion}");
                }
            }
        }
    }
    /** Path to modules root inside host app */
    public const MODULES_DIR = 'Modules';

    /** Path to cache file relative to base path */
    public const CACHE_FILE = 'bootstrap/cache/modules.php';

    /** Root modules enable/disable registry file */
    public const REGISTRY_FILE = 'modules.json';

    /** @var array<string,bool> */
    protected array $registry = [];

    /** @var array<string,array<string,mixed>> */
    protected array $manifests = [];

    /** @var array<string,bool> Providers already registered */
    protected array $registeredProviders = [];

    /**
     * ModuleManager constructor.
     *
     * @param string $basePath Path to the application base directory.
     * @param Filesystem $files Filesystem instance (default: new Filesystem).
     * @param CacheRepository|null $cache Optional cache repository.
     */
    public function __construct(
        protected readonly string $basePath,
        protected readonly Filesystem $files = new Filesystem(),
        protected ?CacheRepository $cache = null
    ) {
        $this->cache = $cache; // optional
        $this->loadRegistry();
    }

    /**
     * Load the modules.json registry file.
     */
    protected function loadRegistry(): void
    {
        $file = $this->basePath . DIRECTORY_SEPARATOR . self::REGISTRY_FILE;
        if ($this->files->exists($file)) {
            $json = $this->files->get($file);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $this->registry = array_map(fn ($v) => (bool) $v, $decoded);
            }
        }
    }

    /**
     * Persist registry to disk.
     */
    protected function saveRegistry(): void
    {
        $file = $this->basePath . DIRECTORY_SEPARATOR . self::REGISTRY_FILE;
        $this->files->put($file, json_encode($this->registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Discover available modules by directories.
     * @return array<string,string> [name => path]
     */
    public function discover(): array
    {
        $modulesRoot = $this->modulesRoot();
        if (! $this->files->isDirectory($modulesRoot)) {
            return [];
        }
        $directories = collect($this->files->directories($modulesRoot));
        return $directories->mapWithKeys(function (string $dir) {
            $name = basename($dir);
            return [$name => $dir];
        })->all();
    }

    /**
     * Get modules root path.
     */
    public function modulesRoot(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . self::MODULES_DIR;
    }

    /**
     * Determine if module is enabled.
     */
    public function enabled(string $name): bool
    {
        return $this->registry[$name] ?? false;
    }

    /**
     * Return all enabled module names.
     * @return string[]
     */
    public function enabledModules(): array
    {
        return array_keys(array_filter($this->registry, fn($v) => $v === true));
    }

    /**
     * Enable a module.
     */
    public function enable(string $name): void
    {
        $this->registry[$name] = true;
        $this->saveRegistry();
        $this->forgetCache();
    }

    /**
     * Disable a module.
     */
    public function disable(string $name): void
    {
        $this->registry[$name] = false;
        $this->saveRegistry();
        $this->forgetCache();
    }

    /**
     * Get path to module.
     */
    public function path(string $name): ?string
    {
        $all = $this->discover();
        return $all[$name] ?? null;
    }

    /**
     * Load manifest for module and memoize.
     * @return array<string,mixed>
     */
    public function manifest(string $name): array
    {
        if (! isset($this->manifests[$name])) {
            $path = $this->path($name);
            if (! $path) {
                throw new InvalidArgumentException("Module [{$name}] not found.");
            }
            $manifest = new ModuleManifest($path);
            $this->manifests[$name] = $manifest->toArray();
        }
        return $this->manifests[$name];
    }

    /**
     * Get version for module.
     */
    public function version(string $name): ?string
    {
        return $this->manifest($name)['version'] ?? null;
    }

    /**
     * Invoke a callable within a module using notation "Module@method".
     * Resolves the module's service provider (if defined) and calls the method via the container.
     * Returns null if provider or method unavailable.
     *
     * @param string $target e.g. "Blog@recount" (module name @ method)
     * @param array<int|string,mixed> $parameters
     */
    public function call(string $target, array $parameters = []): mixed
    {
        if (! str_contains($target, '@')) {
            throw new InvalidArgumentException('Module call target must be in the form Module@method');
        }
        [$module, $method] = explode('@', $target, 2);
        if (! $this->enabled($module)) {
            return null; // Silently ignore disabled module.
        }
        $manifest = $this->manifest($module);
        $provider = $manifest['provider'] ?? null;
        if (! $provider || ! class_exists($provider)) {
            return null;
        }
        // Lazy-register provider if not already registered and lazy mode enabled
        if (config('modules.lazy') && ! isset($this->registeredProviders[$provider])) {
            app()->register($provider);
            $this->registeredProviders[$provider] = true;
            // Perform lightweight auto-registration side-effects similar to eager boot
            try {
                $this->afterLazyRegister($module);
            } catch (\Throwable) {
                // ignore failures
            }
        }
        $instance = app($provider);
        if (! method_exists($instance, $method)) {
            return null;
        }
        return app()->call([$instance, $method], $parameters);
    }

    /**
     * Build cache structure and write file.
     */
    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildCache(): array
    {
        $data = [];
        $all = $this->discover();
        $strict = (bool) config('modules.strict_dependencies', true);
        foreach ($all as $name => $path) {
            if (! $this->enabled($name)) {
                continue;
            }
            try {
                $manifest = new ModuleManifest($path);
                $deps = $manifest->dependencies();
                if ($strict) {
                    $missing = array_filter($deps, fn($d) => ! isset($all[$d]) || ! $this->enabled($d));
                    if ($missing) {
                        continue; // Skip due to missing deps
                    }
                }
                $data[$name] = $manifest->toArray();
            } catch (\Throwable $e) {
                // Skip invalid manifests but continue.
            }
        }
        $this->files->put($this->basePath . DIRECTORY_SEPARATOR . self::CACHE_FILE, '<?php return ' . var_export($data, true) . ';');
        return $data;
    }

    /**
     * Forget runtime cache store.
     */
    public function forgetCache(): void
    {
        if ($this->cache) {
            $this->cache->forget('modules.cache');
        }
    }

    /**
     * Get cached modules (build if absent).
     * @return array<string,array<string,mixed>>
     */
    public function cached(): array
    {
        $file = $this->basePath . DIRECTORY_SEPARATOR . self::CACHE_FILE;
        if ($this->files->exists($file)) {
            /** @var array<string,array<string,mixed>> $data */
            $data = include $file;
        return $data;
        }
        return $this->buildCache();
    }

    /**
     * After lazily registering a provider, mimic key eager boot actions (middleware, events, factories).
     */
    protected function afterLazyRegister(string $module): void
    {
        $base = base_path('Modules/' . $module);
        if (config('modules.autoload_factories', true)) {
            $factories = $base . '/Database/Factories';
            if (is_dir($factories)) {
                // Laravel helper available in app context
                if (function_exists('app') && method_exists(app(), 'loadFactoriesFrom')) {
                    app()->loadFactoriesFrom($factories);
                }
            }
        }
        if (config('modules.lazy_auto_register', true)) {
            // Middleware
            $middlewareDir = $base . '/Http/Middleware';
            if (is_dir($middlewareDir)) {
                foreach (glob($middlewareDir . '/*Middleware.php') as $file) {
                    $class = 'Modules\\' . $module . '\\Http\\Middleware\\' . pathinfo($file, PATHINFO_FILENAME);
                    if (class_exists($class)) {
                        $alias = strtolower(preg_replace('/Middleware$/', '', class_basename($class)));
                        app('router')->aliasMiddleware($alias, $class);
                    }
                }
            }
            // Events & listeners
            $eventsDir = $base . '/Events';
            $listenersDir = $base . '/Listeners';
            if (is_dir($eventsDir) && is_dir($listenersDir)) {
                foreach (glob($listenersDir . '/*.php') as $file) {
                    $listenerClass = 'Modules\\' . $module . '\\Listeners\\' . pathinfo($file, PATHINFO_FILENAME);
                    if (! class_exists($listenerClass)) {
                        continue;
                    }
                    try {
                        $ref = new \ReflectionClass($listenerClass);
                        if ($ref->hasMethod('handle')) {
                            $params = $ref->getMethod('handle')->getParameters();
                            if ($params && ($type = $params[0]->getType()) && $type instanceof \ReflectionNamedType) {
                                $eventClass = $type->getName();
                                if (class_exists($eventClass)) {
                                    app('events')->listen($eventClass, $listenerClass);
                                }
                            }
                        }
                    } catch (\Throwable) {
                        // ignore
                    }
                }
            }
        }
    }
}
