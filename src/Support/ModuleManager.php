<?php

declare(strict_types=1);

namespace Modules\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;

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
     * Validate a module name (alphanumeric, no slashes/dots).
     */
    public static function validateModuleName(string $name): bool
    {
        return preg_match('/^[A-Za-z0-9_\-]+$/', $name) === 1;
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

    /** @var array<string,string>|null Cached discovery map */
    protected ?array $discovered = null;

    /** @var array<string,bool> Providers already registered */
    protected array $registeredProviders = [];

    /** @var array<string,object> */
    protected array $providerInstances = [];

    /**
     * Mark a service provider as registered (used by external boot logic / service provider).
     */
    public function markProviderRegistered(string $provider): void
    {
        $this->registeredProviders[$provider] = true;
    }

    /**
     * Store a provider instance for later method calls.
     */
    public function storeProviderInstance(string $provider, object $instance): void
    {
        $this->providerInstances[$provider] = $instance;
        $this->registeredProviders[$provider] = true;
    }

    /**
     * Resolve a previously registered provider instance.
     */
    protected function providerInstance(string $provider): ?object
    {
        if (isset($this->providerInstances[$provider])) {
            return $this->providerInstances[$provider];
        }
        // Fallback: attempt to fetch from application provider list
        if (function_exists('app') && method_exists(app(), 'getProviders')) {
            $list = app()->getProviders($provider);
            if ($list && isset($list[0])) {
                return $this->providerInstances[$provider] = $list[0];
            }
        }
        return null;
    }

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
        $start = microtime(true);
        $file = $this->basePath . DIRECTORY_SEPARATOR . self::REGISTRY_FILE;
        if ($this->files->exists($file)) {
            $json = $this->files->get($file);
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $this->registry = array_map(fn ($v) => (bool) $v, $decoded);
            }
        }
        $duration = microtime(true) - $start;
        Log::debug('[PERF] loadRegistry: ' . number_format($duration, 4) . 's');
    }

    /**
     * Force reload of the modules.json registry (used in tests after restoration).
     */
    public function reloadRegistry(): void
    {
        $this->registry = [];
        $this->loadRegistry();
    }

    /**
     * Persist registry to disk.
     */
    protected function saveRegistry(): void
    {
    $start = microtime(true);
    $file = $this->basePath . DIRECTORY_SEPARATOR . self::REGISTRY_FILE;
    $this->files->put($file, json_encode($this->registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $duration = microtime(true) - $start;
    Log::debug('[PERF] saveRegistry: ' . number_format($duration, 4) . 's');
    }

    /**
     * Raw registry map (name => enabled bool).
     * @return array<string,bool>
     */
    public function registry(): array
    {
        return $this->registry;
    }

    /**
     * Add a module to registry (defaults to disabled) if not present.
     */
    public function addToRegistry(string $name, bool $enabled = false): void
    {
        if (! array_key_exists($name, $this->registry)) {
            $this->registry[$name] = $enabled;
            $this->saveRegistry();
            $this->forgetCache();
        }
    }

    /**
     * Remove a module from registry if present.
     */
    public function pruneFromRegistry(string $name): void
    {
        if (array_key_exists($name, $this->registry)) {
            unset($this->registry[$name]);
            $this->saveRegistry();
            $this->forgetCache();
        }
    }

    /**
     * Discover available modules by directories.
     * @return array<string,string> [name => path]
     */
    public function discover(): array
    {
        $start = microtime(true);
        if ($this->discovered !== null) {
            return $this->discovered;
        }
        $modulesRoot = $this->modulesRoot();
        if (! $this->files->isDirectory($modulesRoot)) {
            $duration = microtime(true) - $start;
            Log::debug('[PERF] discover: ' . number_format($duration, 4) . 's (no modules root)');
            return $this->discovered = [];
        }
        $directories = collect($this->files->directories($modulesRoot));
        $result = $directories->mapWithKeys(function (string $dir) {
            $name = basename($dir);
            return [$name => $dir];
        })->all();
        $duration = microtime(true) - $start;
    Log::debug('[PERF] discover: ' . number_format($duration, 4) . 's');
        return $this->discovered = $result;
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
        $start = microtime(true);
        $this->registry[$name] = true;
        $this->saveRegistry();
        $this->forgetCache();
        $this->discovered = null; // force re-discovery if new module added externally

        // If in eager mode (non-lazy), attempt to (re)register the module's provider immediately.
        if (! config('modules.lazy')) {
            try {
                $manifest = $this->manifest($name);
                $provider = $manifest['provider'] ?? null;
                if ($provider && class_exists($provider) && ! isset($this->registeredProviders[$provider])) {
                    $instance = app()->register($provider);
                    if (is_object($instance)) {
                        $this->storeProviderInstance($provider, $instance);
                    } else {
                        $this->registeredProviders[$provider] = true; // fallback flag
                    }
                }
            } catch (\Throwable) {
                // swallow – enabling should not hard fail on provider issues
            }
        }
        $duration = microtime(true) - $start;
        Log::debug('[PERF] enable(' . $name . '): ' . number_format($duration, 4) . 's');
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
        $start = microtime(true);
        if (! isset($this->manifests[$name])) {
            $path = $this->path($name);
            if (! $path) {
                throw new InvalidArgumentException("Module [{$name}] not found.");
            }
            $manifest = new ModuleManifest($path);
            $this->manifests[$name] = $manifest->toArray();
        }
        $duration = microtime(true) - $start;
        Log::debug('[PERF] manifest(' . $name . '): ' . number_format($duration, 4) . 's');
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
        $start = microtime(true);
        if (! str_contains($target, '@')) {
            throw new InvalidArgumentException('Module call target must be in the form Module@method');
        }
        [$module, $method] = explode('@', $target, 2);
        if (! $this->enabled($module)) {
            $duration = microtime(true) - $start;
            Log::debug('[PERF] call(' . $target . '): ' . number_format($duration, 4) . 's (disabled)');
            return null; // Silently ignore disabled module.
        }
        $manifest = $this->manifest($module);
        $provider = $manifest['provider'] ?? null;
        if (! $provider || ! class_exists($provider)) {
            $duration = microtime(true) - $start;
            Log::debug('[PERF] call(' . $target . '): ' . number_format($duration, 4) . 's (no provider)');
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
        $instance = $this->providerInstance($provider);
        if (! $instance && isset($this->registeredProviders[$provider]) && ! config('modules.lazy')) {
            // Eager mode but instance not yet stored – try resolving directly
            try {
                if (app()->bound($provider)) {
                    $resolved = app($provider);
                    if (is_object($resolved)) {
                        $this->storeProviderInstance($provider, $resolved);
                        $instance = $resolved;
                    }
                }
            } catch (\Throwable) {
                // ignore and continue to lazy branch
            }
        }
        if (! $instance && config('modules.lazy')) {
            // Lazy registration scenario: register now
            try {
                $reg = app()->register($provider);
                if (is_object($reg)) {
                    $this->storeProviderInstance($provider, $reg);
                    $instance = $reg;
                }
            } catch (\Throwable) {
                $duration = microtime(true) - $start;
                Log::debug('[PERF] call(' . $target . '): ' . number_format($duration, 4) . 's (lazy fail)');
                return null;
            }
        }
        if (! $instance || ! method_exists($instance, $method)) {
            $duration = microtime(true) - $start;
            Log::debug('[PERF] call(' . $target . '): ' . number_format($duration, 4) . 's (no method)');
            return null;
        }
        $result = app()->call([$instance, $method], $parameters);
        $duration = microtime(true) - $start;
        Log::debug('[PERF] call(' . $target . '): ' . number_format($duration, 4) . 's');
        return $result;
    }

    /**
     * Build cache structure and write file.
     */
    /**
     * @return array<string, array<string, mixed>>
     */
    public function buildCache(): array
    {
        $start = microtime(true);
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
        $duration = microtime(true) - $start;
        Log::debug('[PERF] buildCache: ' . number_format($duration, 4) . 's');
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
     * Flush all cached manifest arrays (force re-read on next access).
     */
    public function flushManifestCache(): void
    {
        $this->manifests = [];
    }

    /**
     * Refresh a single module manifest cache entry.
     */
    public function refreshManifest(string $name): void
    {
        unset($this->manifests[$name]);
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
