<?php

declare(strict_types=1);

namespace Modules;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Modules\Support\ModuleManager;
use Illuminate\Support\Facades\Log;

/**
 * Package service provider responsible for:
 * - Registering the ModuleManager singleton
 * - Automatically discovering and registering enabled module service providers
 * - Publishing package configuration (future phases)
 */
class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('modules.manager', function ($app) {
            /** @var \Illuminate\Foundation\Application $app */
            return new ModuleManager(base_path(), new Filesystem());
        });

        // Merge default config (placeholder for later expansion)
        $this->mergeConfigFrom(__DIR__ . '/Config/modules.php', 'modules');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** @var ModuleManager $manager */
        $manager = $this->app->make('modules.manager');

        // Iterate cached enabled modules and register their providers.
        $lazy = config('modules.lazy', false);
        if (! $lazy) {
            foreach ($manager->cached() as $name => $manifest) {
                $provider = $manifest['provider'] ?? null;
                if ($provider && class_exists($provider)) {
                    try {
                        $this->app->register($provider);
                        $this->autoRegisterModule($name, $manifest);
                        // Register publishable resources per module when in console
                        if ($this->app->runningInConsole()) {
                            $this->registerModulePublishables($name);
                        }
                        // Auto-load factories if enabled
                        if (config('modules.autoload_factories', true)) {
                            $factoriesPath = base_path('Modules/' . $name . '/Database/Factories');
                            if (is_dir($factoriesPath)) {
                                $this->loadFactoriesFrom($factoriesPath);
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed registering module provider', [
                            'module' => $name,
                            'provider' => $provider,
                            'exception' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        // Placeholder for publishing assets / configs in later phases.
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/modules.php' => config_path('modules.php'),
            ], 'modules-config');

            // Register artisan commands
            $this->commands([
                \Modules\Console\MakeModuleCommand::class,
                \Modules\Console\EnableModuleCommand::class,
                \Modules\Console\DisableModuleCommand::class,
                \Modules\Console\ListModulesCommand::class,
                \Modules\Console\ClearModulesCacheCommand::class,
                \Modules\Console\MakeControllerCommand::class,
                \Modules\Console\MakeModelCommand::class,
                \Modules\Console\MakeEventCommand::class,
                \Modules\Console\MakeListenerCommand::class,
                \Modules\Console\ValidateModulesCommand::class,
                \Modules\Console\GraphModulesCommand::class,
                \Modules\Console\MakeMigrationCommand::class,
                \Modules\Console\MakeSeederCommand::class,
                \Modules\Console\MakeFactoryCommand::class,
                \Modules\Console\MakeTestCommand::class,
                \Modules\Console\MakeMiddlewareCommand::class,
            ]);
        }
    }

    /**
     * Auto-register middleware aliases & events/listeners for a module.
     * @param array<string,mixed> $manifest
     */
    protected function autoRegisterModule(string $name, array $manifest): void
    {
        $base = base_path('Modules/' . $name);
        // Middleware auto-registration: scan Http/Middleware for *Middleware.php
        $middlewareDir = $base . '/Http/Middleware';
        if (is_dir($middlewareDir)) {
            foreach (glob($middlewareDir . '/*Middleware.php') as $file) {
                $class = $this->classFromPath($file, "Modules\\{$name}\\Http\\Middleware");
                if (class_exists($class)) {
                    $alias = strtolower(preg_replace('/Middleware$/', '', class_basename($class)));
                    $router = $this->app['router'];
                    $router->aliasMiddleware($alias, $class);
                }
            }
        }
        // Event/listener auto-registration: if Events & Listeners directories exist
        $eventsDir = $base . '/Events';
        $listenersDir = $base . '/Listeners';
        if (is_dir($eventsDir) && is_dir($listenersDir)) {
            $dispatcher = $this->app['events'];
            foreach (glob($listenersDir . '/*.php') as $file) {
                $listenerClass = $this->classFromPath($file, "Modules\\{$name}\\Listeners");
                if (! class_exists($listenerClass)) {
                    continue;
                }
                // Infer handled event from handle method type-hint if possible (simple reflection)
                try {
                    $ref = new \ReflectionClass($listenerClass);
                    if ($ref->hasMethod('handle')) {
                        $params = $ref->getMethod('handle')->getParameters();
                        if ($params && ($type = $params[0]->getType()) && $type instanceof \ReflectionNamedType) {
                            $eventClass = $type->getName();
                            if (class_exists($eventClass)) {
                                $dispatcher->listen($eventClass, $listenerClass);
                            }
                        }
                    }
                } catch (\Throwable) {
                    // ignore failures
                }
            }
        }
    }

    protected function classFromPath(string $file, string $namespace): string
    {
        return rtrim($namespace, '\\') . '\\' . pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * Register publishable assets (config, views) for a module under a unified tag: module-{name}
     */
    protected function registerModulePublishables(string $name): void
    {
        $lower = strtolower($name);
        $base = base_path('Modules/' . $name);
        $paths = [];
        $config = $base . '/Config/config.php';
        if (is_file($config)) {
            $paths[$config] = config_path($lower . '.php');
        }
        $views = $base . '/Resources/views';
        if (is_dir($views)) {
            $paths[$views] = resource_path('views/vendor/' . $lower);
        }
        if ($paths) {
            $this->publishes($paths, 'module-' . $lower);
            // Also aggregate all module resources under a global tag
            $this->publishes($paths, 'modules-resources');
        }
    }
}
