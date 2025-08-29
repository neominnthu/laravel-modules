<?php

declare(strict_types=1);

use Modules\Facades\Module;

if (! function_exists('module_path')) {
    /**
     * Get the absolute path to a module by name.
     */
    function module_path(string $name): ?string
    {
        return Module::path($name);
    }
}

if (! function_exists('module_enabled')) {
    /**
     * Determine if a module is enabled.
     */
    function module_enabled(string $name): bool
    {
        return Module::enabled($name);
    }
}

if (! function_exists('module_call')) {
    /**
     * Call a method on a module service provider using Module@method notation.
     * Returns mixed or null if unavailable.
     * @param array<int|string,mixed> $parameters
     */
    function module_call(string $target, array $parameters = []): mixed
    {
        return Module::call($target, $parameters);
    }
}

if (! function_exists('module_version')) {
    /**
     * Get a module version (or null if unknown).
     */
    function module_version(string $name): ?string
    {
        return Module::version($name);
    }
}

if (! function_exists('module_manifest')) {
    /**
     * Get raw manifest array for a module.
     * @return array<string,mixed>
     */
    function module_manifest(string $name): array
    {
        return Module::manifest($name);
    }
}

if (! function_exists('modules_manager')) {
    /**
     * Resolve the underlying ModuleManager instance.
     */
    function modules_manager(): mixed
    {
        return app('modules.manager');
    }
}

if (! function_exists('modules_enabled')) {
    /**
     * Get an array of currently enabled module names.
     * @return string[]
     */
    function modules_enabled(): array
    {
        $manager = modules_manager();
        return $manager ? $manager->enabledModules() : [];
    }
}
